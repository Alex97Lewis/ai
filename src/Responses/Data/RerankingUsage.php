<?php

namespace Laravel\Ai\Responses\Data;

readonly class RerankingUsage extends Usage
{
    /**
     * @param  int  $inputTokens  Total tokens across the query and the documents.
     * @param  float|null  $searchUnits  Billed search units, or null when unreported.
     * @param  float|null  $cost  Billed cost of the request in credits, or null when unreported.
     */
    public function __construct(
        int $inputTokens = 0,
        public ?float $searchUnits = null,
        ?float $cost = null,
    ) {
        parent::__construct($inputTokens, cost: $cost);
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'search_units' => $this->searchUnits,
        ];
    }
}
