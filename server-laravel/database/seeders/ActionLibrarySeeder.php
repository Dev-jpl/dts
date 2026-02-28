<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActionLibrarySeeder extends Seeder
{
    public function run(): void
    {
        $actions = [
            [
                'name'                  => 'Appropriate Action',
                'type'                  => 'FA',
                'reply_is_terminal'     => false,
                'requires_proof'        => true,
                'proof_description'     => null,
                'default_urgency_level' => null,
            ],
            [
                'name'                  => 'Urgent Action',
                'type'                  => 'FA',
                'reply_is_terminal'     => false,
                'requires_proof'        => true,
                'proof_description'     => null,
                'default_urgency_level' => 'Urgent', // auto-locked, cannot be overridden
            ],
            [
                'name'                  => 'Dissemination of Information',
                'type'                  => 'FI',
                'reply_is_terminal'     => false,
                'requires_proof'        => false,
                'proof_description'     => null,
                'default_urgency_level' => null,
            ],
            [
                'name'                  => 'Comment/Reaction/Response',
                'type'                  => 'FA',
                'reply_is_terminal'     => true,
                'requires_proof'        => false,
                'proof_description'     => null,
                'default_urgency_level' => null,
            ],
            [
                'name'                  => 'Compliance/Implementation',
                'type'                  => 'FA',
                'reply_is_terminal'     => false,
                'requires_proof'        => true,
                'proof_description'     => null,
                'default_urgency_level' => null,
            ],
            [
                'name'                  => 'Endorsement/Recommendation',
                'type'                  => 'FA',
                'reply_is_terminal'     => false,
                'requires_proof'        => true,
                'proof_description'     => null,
                'default_urgency_level' => null,
            ],
            [
                'name'                  => 'Coding/Deposit/Preparation',
                'type'                  => 'FA',
                'reply_is_terminal'     => false,
                'requires_proof'        => true,
                'proof_description'     => null,
                'default_urgency_level' => null,
            ],
            [
                'name'                  => 'Follow Up',
                'type'                  => 'FA',
                'reply_is_terminal'     => false,
                'requires_proof'        => false,
                'proof_description'     => null,
                'default_urgency_level' => null,
            ],
            [
                'name'                  => 'Investigation/Verification',
                'type'                  => 'FA',
                'reply_is_terminal'     => false,
                'requires_proof'        => true,
                'proof_description'     => null,
                'default_urgency_level' => null,
            ],
            [
                'name'                  => 'Your Information',
                'type'                  => 'FI',
                'reply_is_terminal'     => false,
                'requires_proof'        => false,
                'proof_description'     => null,
                'default_urgency_level' => null,
            ],
            [
                'name'                  => 'Draft of Reply',
                'type'                  => 'FA',
                'reply_is_terminal'     => true,
                'requires_proof'        => false,
                'proof_description'     => null,
                'default_urgency_level' => null,
            ],
            [
                'name'                  => 'Approval',
                'type'                  => 'FA',
                'reply_is_terminal'     => false,
                'requires_proof'        => true,
                'proof_description'     => null,
                'default_urgency_level' => null,
            ],
        ];

        foreach ($actions as $action) {
            DB::table('action_library')->updateOrInsert(
                ['name' => $action['name']],
                array_merge($action, [
                    'isActive'   => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
