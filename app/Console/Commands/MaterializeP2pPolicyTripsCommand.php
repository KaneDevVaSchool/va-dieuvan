<?php

namespace App\Console\Commands;

use App\Models\P2pPolicyTerm;
use App\Models\User;
use App\Services\P2pPolicy\P2pPolicyActivationService;
use Illuminate\Console\Command;

class MaterializeP2pPolicyTripsCommand extends Command
{
    protected $signature = 'policy:materialize-trips {--term=* : Restrict to p2p_policy_term id(s)}';

    protected $description = 'Materialize P2P Policy trips for active terms within the configured horizon.';

    public function handle(P2pPolicyActivationService $activation): int
    {
        $horizon = max(1, (int) config('dispatch.p2p_policy_horizon_days', 21));
        $systemUserId = (int) (User::query()->orderBy('id')->value('id') ?? 0);

        if ($systemUserId < 1) {
            $this->error('No users in database; cannot materialize P2P Policy trips.');

            return self::FAILURE;
        }

        /** @var array<int, mixed> */
        $termIdsRaw = $this->option('term');
        $termIds = array_values(array_unique(array_filter(array_map('intval', (array) $termIdsRaw))));

        $q = P2pPolicyTerm::query()->where('status', 'active');
        if ($termIds !== []) {
            $q->whereKey($termIds);
        }

        $totalCreated = 0;
        foreach ($q->cursor() as $term) {
            $created = $activation->materializeHorizonForTerm($term, $horizon, $systemUserId);
            $totalCreated += $created;
            if ($created > 0) {
                $this->info("Term #{$term->getKey()}: +{$created} trip(s)");
            }
        }

        $this->info('Total P2P Policy trips created: '.$totalCreated);

        return self::SUCCESS;
    }
}
