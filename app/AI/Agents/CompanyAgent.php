<?php

namespace App\AI\Agents;

use App\AI\DTO\AgentRequest;
use App\AI\DTO\AgentResponse;
use App\AI\Services\AiGateway;
use App\AI\Services\PromptService;
use App\AI\Tools\SearchCompaniesTool;

class CompanyAgent
{
    protected AiGateway $gateway;

    protected PromptService $prompts;

    protected SearchCompaniesTool $searchCompanies;

    public function __construct(
        AiGateway $gateway,
        PromptService $prompts,
        SearchCompaniesTool $searchCompanies
    ) {
        $this->gateway = $gateway;
        $this->prompts = $prompts;
        $this->searchCompanies = $searchCompanies;
    }

    /**
     * Handle company-related conversations.
     *
     * Company information comes from the existing company and industrial
     * company systems. The AI does not invent company facts.
     */
    public function handle(
        AgentRequest $request
    ): AgentResponse {
        $filters = $this->extractFilters(
            $request->message
        );

        $companyResults = $this->searchCompanies->execute(
            $filters
        );

        $context = $request->context;

        $context['company_search'] = $companyResults;

        $agentRequest = new AgentRequest(
            $request->message,
            $request->userId,
            'company',
            $context,
            $request->metadata
        );

        return $this->gateway->generate(
            $agentRequest,
            $this->prompts->company()
        );
    }

    /**
     * Extract lightweight filters from the user's request.
     *
     * The actual company lookup remains inside SearchCompaniesTool,
     * which uses the existing database models.
     */
    protected function extractFilters(
        string $message
    ): array {
        $filters = [
            'search' => trim($message),
            'source' => 'all',
            'limit' => 10,
        ];

        $lower = strtolower($message);

        /*
         * Industrial-company requests.
         */
        $industrialKeywords = [
            'industrial',
            'manufacturing',
            'factory',
            'plant',
            'industrial area',
            'industrial estate',
            'industrial park',
            'forging',
            'steel',
            'automobile',
            'automotive',
            'cnc',
            'casting',
            'foundry',
            'production',
            'machinery',
            'engineering',
            'sidcul',
            'midc',
            'gidc',
            'riico',
            'apiic',
            'psiec',
        ];

        foreach ($industrialKeywords as $keyword) {
            if (
                strpos(
                    $lower,
                    strtolower($keyword)
                ) !== false
            ) {
                $filters['source'] = 'industrial';
                break;
            }
        }

        /*
         * Explicit generic-company request.
         */
        if (
            strpos($lower, 'corporate company') !== false ||
            strpos($lower, 'software company') !== false ||
            strpos($lower, 'tech company') !== false
        ) {
            $filters['source'] = 'generic';
        }

        /*
         * Industry filters.
         */
        $industries = [
            'technology',
            'software',
            'information technology',
            'it',
            'fintech',
            'banking',
            'finance',
            'automobile',
            'automotive',
            'manufacturing',
            'forging',
            'steel',
            'metals',
            'energy',
            'renewable energy',
            'pharmaceutical',
            'pharmaceuticals',
            'healthcare',
            'telecom',
            'electronics',
            'semiconductor',
            'textile',
            'logistics',
            'retail',
            'ecommerce',
            'construction',
            'engineering',
            'aerospace',
            'defence',
            'defense',
        ];

        foreach ($industries as $industry) {
            if (
                strpos(
                    $lower,
                    strtolower($industry)
                ) !== false
            ) {
                $filters['industry'] = $industry;
                break;
            }
        }

        /*
         * Country filter.
         */
        $countries = [
            'india',
            'united states',
            'usa',
            'uk',
            'united kingdom',
            'canada',
            'australia',
            'germany',
            'singapore',
        ];

        foreach ($countries as $country) {
            if (
                strpos(
                    $lower,
                    strtolower($country)
                ) !== false
            ) {
                $filters['country'] = $country;
                break;
            }
        }

        /*
         * Common Indian locations.
         *
         * SearchCompaniesTool can use the search text itself to find
         * companies associated with these locations.
         */
        $locations = [
            'ludhiana',
            'hoshiarpur',
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

        return $filters;
    }

    /**
     * Agent identifier.
     */
    public function name(): string
    {
        return 'company';
    }

    /**
     * Description used by the router/agent registry.
     */
    public function description(): string
    {
        return 'Searches existing companies and industrial companies and explains their industries, locations, career opportunities, verification information and available company data.';
    }
}
