<?php

namespace App\AI\Agents;

use App\AI\DTO\AgentRequest;
use App\AI\DTO\AgentResponse;
use App\AI\Services\AiGateway;
use App\AI\Tools\SearchPropertiesTool;

// Property questions in the site assistant: finds live listings and projects in our database and links to them.
// The AI phrases the answer from those results only; without an AI key the agent still answers from the results.
class PropertyAgent
{
    public function __construct(private AiGateway $gateway, private SearchPropertiesTool $search) {}

    public function handle(AgentRequest $request): AgentResponse
    {
        $filters = $this->search->parse($request->message);
        $found = $this->search->execute($filters);
        $what = SearchPropertiesTool::describe($found['filters']);

        $links = array_map(fn ($l) => [$l['title'] . ' · ' . $l['price'], $l['url']], array_slice($found['listings'], 0, 4));
        foreach (array_slice($found['projects'], 0, 2) as $p) $links[] = [$p['name'] . ($p['price'] ? ' · ' . $p['price'] : ''), $p['url']];
        $links[] = [$found['total'] ? 'See all ' . $found['total'] . ' ' . $what : 'Search ' . $what, $found['search_url']];
        if ($found['city_url']) $links[] = ['Real estate in ' . $found['filters']['city_name'], $found['city_url']];
        if (!$found['total']) $links[] = ['Post your property free', route('properties.post')];

        $context = $request->context + ['property_search' => ['asked_for' => $what, 'results' => $found]];
        $ai = $this->gateway->generate(new AgentRequest($request->message, $request->userId, 'property', $context, $request->metadata), $this->instructions());
        return AgentResponse::success($ai->success ? $ai->message : $this->fallback($found, $what), 'property', ['links' => $links], [], ['ai' => $ai->success, 'filters' => $found['filters']]);
    }

    private function instructions(): string
    {
        return "You are the property assistant of an Indian real-estate marketplace. Answer briefly in the user's language (English or Hinglish).\n"
            . "Use ONLY context.property_search.results: the listings, projects and totals found in our database. Never invent listings, prices, builders, offers or localities.\n"
            . "If nothing matched, say so and suggest widening the budget, locality or BHK, or posting a requirement. Links are shown under your answer — don't paste URLs.\n"
            . "Prices are in ₹ with lakh / crore. For pre-leased offices you may mention the annual rental yield given. Don't give legal or tax advice beyond pointing to the site's legal services.";
    }

    private function fallback(array $found, string $what): string
    {
        if (!$found['total'] && !$found['projects']) return "I couldn't find live listings for $what right now. Try a bigger budget or a nearby locality, or post your requirement.";
        $out = [$found['total'] ? "I found {$found['total']} $what. The newest:" : "No live listings for $what yet, but these projects match:"];
        foreach (array_slice($found['listings'], 0, 4) as $l) $out[] = "• {$l['title']} — {$l['price']}" . ($l['area'] ? ", {$l['area']}" : '') . ($l['pre_leased_yield'] ? ", {$l['pre_leased_yield']}% rental yield" : '');
        foreach (array_slice($found['projects'], 0, 2) as $p) $out[] = "• Project {$p['name']}" . ($p['builder'] ? " by {$p['builder']}" : '') . ($p['price'] ? " — {$p['price']}" : '') . " ({$p['status']})";
        return implode("\n", $out);
    }
}
