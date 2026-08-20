<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = ['Électricité', 'Plomberie', 'Climatisation', 'Menuiserie', 'Peinture', 'Informatique/Réseau', 'Sécurité incendie'];

        foreach ($skills as $skill) {
            Skill::firstOrCreate(['name' => $skill]);
        }
    }
}