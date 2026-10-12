<?php

namespace Tests\Feature;

use App\Jobs\SendBellPush;
use App\Models\User;
use App\Notifications\BellPushNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Notifications push : « la cloche dans la poche ». Un appareil s'abonne pour la
 * personne connectée ; chaque notification de la cloche lui est copiée en push.
 */
class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://fcm.googleapis.com/fcm/send/appareil-1';

    private function subscribe(User $user, string $endpoint = self::ENDPOINT)
    {
        return $this->actingAs($user)->postJson(route('push-subscriptions.store'), [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'cle-publique', 'auth' => 'jeton'],
            'contentEncoding' => 'aes128gcm',
        ]);
    }

    /** Une notification de cloche quelconque. */
    private function bell(string $message = 'Nouvel OT URGENTE — Chambre 305'): BaseNotification
    {
        return new class($message) extends BaseNotification {
            public function __construct(private string $message)
            {
            }

            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toArray(object $notifiable): array
            {
                return ['message' => $this->message, 'work_order_id' => 42];
            }
        };
    }

    public function test_a_device_subscribes_for_the_signed_in_person_and_follows_a_shared_post(): void
    {
        $first = User::factory()->reception()->create();
        $next = User::factory()->reception()->create();

        $this->subscribe($first)->assertNoContent()->assertSessionHas('push_endpoint', self::ENDPOINT);
        $this->assertSame(1, $first->pushSubscriptions()->count());

        // Poste partagé : le même appareil passe à la personne qui se connecte ensuite.
        $this->subscribe($next)->assertNoContent();
        $this->assertSame(0, $first->pushSubscriptions()->count());
        $this->assertSame(1, $next->pushSubscriptions()->count());
    }

    public function test_only_secure_push_addresses_are_accepted(): void
    {
        $this->subscribe(User::factory()->create(), 'http://evil.test/push')->assertUnprocessable();
    }

    public function test_bell_is_copied_in_push_only_for_people_with_a_device(): void
    {
        Bus::fake([SendBellPush::class]);
        $withPhone = User::factory()->technicien()->create();
        $without = User::factory()->technicien()->create();
        $this->subscribe($withPhone);

        $withPhone->notify($this->bell());
        $without->notify($this->bell());

        Bus::assertDispatched(SendBellPush::class, fn ($job) => $job->user->is($withPhone)
            && $job->notificationId === $withPhone->notifications()->first()->id);
        Bus::assertDispatchedTimes(SendBellPush::class, 1);
    }

    public function test_push_shows_the_bell_message_and_opens_its_page(): void
    {
        $user = User::factory()->technicien()->create();
        $user->notify($this->bell());
        $bell = $user->notifications()->first();
        Notification::fake();

        (new SendBellPush($user, $bell->id))->handle();

        Notification::assertSentTo($user, BellPushNotification::class, function ($push) use ($user, $bell) {
            $message = $push->toWebPush($user, $push)->toArray();

            return $message['body'] === 'Nouvel OT URGENTE — Chambre 305'
                && $message['data']['url'] === route('notifications.open', $bell->id);
        });
    }

    public function test_no_push_once_the_bell_was_already_read(): void
    {
        $user = User::factory()->technicien()->create();
        $user->notify($this->bell());
        $user->notifications()->first()->markAsRead();
        Notification::fake();

        (new SendBellPush($user, $user->notifications()->first()->id))->handle();

        Notification::assertNothingSent();
    }

    public function test_opening_a_push_marks_it_read_and_goes_to_its_page(): void
    {
        $user = User::factory()->technicien()->create();
        $user->notify($this->bell());
        $bell = $user->notifications()->first();

        $this->actingAs(User::factory()->technicien()->create())
            ->get(route('notifications.open', $bell->id))->assertNotFound();

        $this->actingAs($user)->get(route('notifications.open', $bell->id))
            ->assertRedirect(route('work-orders.show', 42));
        $this->assertNotNull($bell->fresh()->read_at);
    }

    public function test_logging_out_unsubscribes_the_device(): void
    {
        $user = User::factory()->reception()->create();
        $this->subscribe($user);

        $this->post(route('logout'));

        $this->assertSame(0, $user->pushSubscriptions()->count());
    }

    public function test_the_notifications_page_offers_to_enable_push(): void
    {
        $this->actingAs(User::factory()->technicien()->create())->get(route('notifications.index'))
            ->assertOk()->assertSee('Notifications sur cet appareil')
            ->assertSee('name="vapid-public-key"', false);
    }
}
