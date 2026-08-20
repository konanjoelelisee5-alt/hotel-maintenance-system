<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Notifications') }}
            </h2>
            @if (auth()->user()->unreadNotifications->isNotEmpty())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="text-sm text-indigo-600 hover:underline">
                        Tout marquer comme lu
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm rounded-lg divide-y">
                @forelse ($notifications as $notification)
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                        @csrf
                        <button type="submit" class="w-full text-left p-4 hover:bg-gray-50 flex justify-between items-start gap-4">
                            <div>
                                <p class="text-sm {{ $notification->read_at ? 'text-gray-500' : 'text-gray-900 font-medium' }}">
                                    {{ $notification->data['message'] }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                            @unless ($notification->read_at)
                                <span class="shrink-0 h-2 w-2 bg-blue-600 rounded-full mt-2"></span>
                            @endunless
                        </button>
                    </form>
                @empty
                    <p class="p-6 text-sm text-gray-500 text-center">Aucune notification pour le moment.</p>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $notifications->links() }}
            </div>

        </div>
    </div>
</x-app-layout>