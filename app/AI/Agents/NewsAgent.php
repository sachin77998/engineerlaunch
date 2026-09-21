<?php

namespace App\AI\Agents;

use App\AI\DTO\AgentRequest;
use App\AI\DTO\AgentResponse;
use App\AI\Services\AiGateway;
use App\AI\Services\PromptService;
use App\AI\Tools\SearchNewsTool;

class NewsAgent
{
    protected AiGateway $gateway;

    protected PromptService $prompts;

    protected SearchNewsTool $searchNews;

    public function __construct(
        AiGateway $gateway,
        PromptService $prompts,
        SearchNewsTool $searchNews
    ) {
        $this->gateway = $gateway;
        $this->prompts = $prompts;
        $this->searchNews = $searchNews;
    }

    /**
     * Handle latest-news and industry-intelligence conversations.
     *
     * The agent retrieves existing published articles first and then
     * uses the AI to explain them in the context requested by the user.
     */
    public function handle(
        AgentRequest $request
    ): AgentResponse {
        $filters = $this->extractFilters(
            $request->message
        );

        $newsResults = $this->searchNews->execute(
            $filters
        );

        $context = $request->context;

        /*
         * Real platform news results.
         */
        $context['news_search'] = $newsResults;

        /*
         * Preserve the original user request so the AI can distinguish
         * between "latest news", "why it matters", career impact, etc.
         */
        $context['news_request'] = [
            'message' => $request->message,
            'filters' => $filters,
        ];

        $agentRequest = new AgentRequest(
            $request->message,
            $request->userId,
            'news',
            $context,
            $request->metadata
        );

        return $this->gateway->generate(
            $agentRequest,
            $this->prompts->news()
        );
    }

    /**
     * Extract lightweight filters from the natural-language request.
     *
     * SearchNewsTool remains responsible for database searching.
     */
    protected function extractFilters(
        string $message
    ): array {
        $filters = [
            'search' => trim($message),
            'limit' => 10,
        ];

        $lower = strtolower($message);

        /*
         * Industry/category detection.
         */
        $categories = [
            'technology',
            'artificial intelligence',
            'ai',
            'software',
            'software development',
            'cyber security',
            'cybersecurity',
            'cloud computing',
            'data science',
            'computer science',
            'programming',
            'mechanical engineering',
            'mechanical',
            'manufacturing',
            'automobile',
            'automotive',
            'electric vehicle',
            'ev',
            'battery',
            'forging',
            'steel',
            'metals',
            'energy',
            'renewable energy',
            'data center',
            'data centers',
            'semiconductor',
            'electronics',
            'telecom',
            'finance',
            'fintech',
            'banking',
            'jobs',
            'hiring',
            'layoffs',
            'salary',
            'career',
            'education',
            'government',
            'policy',
            'business',
            'startups',
            'companies',
        ];

        foreach ($categories as $category) {
            if (
                strpos(
                    $lower,
                    strtolower($category)
                ) !== false
            ) {
                $filters['category'] = $category;
                break;
            }
        }

        /*
         * Company names frequently used in the platform's industrial
         * and technology intelligence searches.
         */
        $companies = [
            'balu forge',
            'balu forge industries',
            'happy forgings',
            'hero cycles',
            'hero motocorp',
            'mahindra',
            'mahindra tractors',
            'tata motors',
            'bharat forge',
            'jindal stainless',
            'jindal steel',
            'jsw steel',
            'maruti suzuki',
            'suzuki motors',
            'honda',
            'bosch',
            'valeо',
            'valeo',
            'motherson',
            'marelli',
            'tafe',
            'bhel',
            'cisco',
            'nvidia',
            'microsoft',
            'google',
            'amazon',
            'adobe',
            'salesforce',
            'ibm',
            'oracle',
        ];

        foreach ($companies as $company) {
            if (
                strpos(
                    $lower,
                    strtolower($company)
                ) !== false
            ) {
                $filters['company'] = $company;
                break;
            }
        }

        /*
         * Industrial keywords.
         */
        $industrialKeywords = [
            'forging',
            'forged',
            'gear',
            'gears',
            'gear manufacturing',
            'steel',
            'stainless steel',
            'specialty steel',
            'casting',
            'foundry',
            'manufacturing',
            'production',
            'automobile',
            'automotive',
            'tractor',
            'tractors',
            'ev',
            'electric vehicle',
            'battery',
            'ring rolling',
            'cnc',
            'machine tools',
            'industrial automation',
            'robotics',
            'plant',
            'factory',
            'industrial',
        ];

        foreach ($industrialKeywords as $keyword) {
            if (
                strpos(
                    $lower,
                    strtolower($keyword)
                ) !== false
            ) {
                $filters['industry'] = $keyword;
                break;
            }
        }

        /*
         * Common Indian locations.
         */
        $locations = [
            'ludhiana',
            'jalandhar',
            'amritsar',
            'rajpura',
            'chandigarh',
            'mohali',
            'patiala',
            'baddi',
            'una',
            'rudrapur',
            'pantnagar',
            'haridwar',
            'manesar',
            'gurugram',
            'gurgaon',
            'bhiwadi',
            'neemrana',
            'sanand',
            'ahmedabad',
            'vadodara',
            'surat',
            'pune',
            'mumbai',
            'nashik',
            'nagpur',
            'hyderabad',
            'bengaluru',
            'bangalore',
            'chennai',
            'coimbatore',
            'delhi',
            'noida',
            'greater noida',
            'jaipur',
            'indore',
            'visakhapatnam',
            'kolkata',
        ];

        foreach ($locations as $location) {
            if (
                strpos(
                    $lower,
                    strtolower($location)
                ) !== false
            ) {
                $filters['location'] = $location;
                break;
            }
        }

        /*
         * Time-oriented requests.
         *
         * SearchNewsTool will use explicit dates when supplied.
         */
        if (
            strpos($lower, 'today') !== false ||
            strpos($lower, 'latest') !== false ||
            strpos($lower, 'recent') !== false
        ) {
            $filters['recent'] = true;
        }

        return $filters;
    }

    /**
     * Agent identifier.
     */
    public function name(): string
    {
        return 'news';
    }

    /**
     * Description used by the router/agent registry.
     */
    public function description(): string
    {
        return 'Searches published platform news and explains technology, industry, company, jobs, salary and career intelligence using the available article data and source information.';
    }
}
