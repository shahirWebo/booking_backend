<?php

namespace Tests\Feature\Queue;

use Illuminate\Contracts\Queue\ShouldQueue;

final class QueuePayloadTestJob implements ShouldQueue
{
    public function handle(): void
    {
        //
    }
}
