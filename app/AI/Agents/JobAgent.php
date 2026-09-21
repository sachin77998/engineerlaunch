<?php

namespace App\AI\Agents;

use App\AI\DTO\AgentRequest;
use App\AI\DTO\AgentResponse;
use App\AI\Services\AiGateway;
use App\AI\Services\PromptService;
use App\AI\Tools\SearchJobsTool;

class JobAgent
{
    protected AiGateway $gateway;

    protected PromptService $prompts;

    protected SearchJobsTool $searchJobs;

    public function __construct(
        AiGateway $gateway,
        PromptService $prompts,
        SearchJobsTool $searchJobs
    ) {
        $this->gateway = $gateway;
        $this->prompts = $prompts;
        $this->searchJobs = $searchJobs;
    }

    /**
     * Handle job-related conversations.
     *
     * The agent first retrieves real jobs from the existing Job system,
     * then gives those results to the AI for natural-language presentation.
     */
    public function handle(
        AgentRequest $request
    ): AgentResponse {
        $filters = $this->extractFilters(
            $request->message
        );

        $jobResults = $this->searchJobs->execute(
            $filters
        );

        $context = $request->context;

        $context['job_search'] = $jobResults;

        $agentRequest = new AgentRequest(
            $request->message,
            $request->userId,
            'job',
            $context,
            $request->metadata
        );

        return $this->gateway->generate(
            $agentRequest,
            $this->prompts->job()
        );
    }

    /**
     * Extract useful search filters from the user's message.
     *
     * This is deliberately lightweight. The actual job search remains
     * inside SearchJobsTool and the existing Job model/scopes.
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
         * Work mode.
         */
        if (
            strpos($lower, 'remote') !== false
        ) {
            $filters['work_mode'] = 'remote';
        } elseif (
            strpos($lower, 'hybrid') !== false
        ) {
            $filters['work_mode'] = 'hybrid';
        } elseif (
            strpos($lower, 'on site') !== false ||
            strpos($lower, 'onsite') !== false
        ) {
            $filters['work_mode'] = 'onsite';
        }

        /*
         * Common employment types.
         */
        if (
            strpos($lower, 'full time') !== false ||
            strpos($lower, 'full-time') !== false
        ) {
            $filters['job_type'] = 'full-time';
        } elseif (
            strpos($lower, 'part time') !== false ||
            strpos($lower, 'part-time') !== false
        ) {
            $filters['job_type'] = 'part-time';
        } elseif (
            strpos($lower, 'internship') !== false ||
            strpos($lower, 'intern') !== false
        ) {
            $filters['job_type'] = 'internship';
        }

        /*
         * Keep explicit technology/role terms in the search.
         *
         * SearchJobsTool will perform the actual DB matching.
         */
        $knownTerms = [
            'java',
            'spring boot',
            'php',
            'laravel',
            'python',
            'django',
            'javascript',
            'typescript',
            'react',
            'angular',
            'node',
            'nodejs',
            'sql',
            'mysql',
            'postgresql',
            'kafka',
            'redis',
            'docker',
            'kubernetes',
            'aws',
            'azure',
            'devops',
            'machine learning',
            'artificial intelligence',
            'data science',
            'software engineer',
            'software developer',
            'backend developer',
            'frontend developer',
            'full stack developer',
            'mechanical engineer',
            'electrical engineer',
            'civil engineer',
            'production engineer',
            'maintenance engineer',
            'quality engineer',
            'cnc operator',
            'vmc operator',
            'die engineer',
            'die maker',
            'tool room',
            'fitter',
            'technician',
            'warehouse',
            'logistics',
            'accounts',
            'purchase',
        ];

        $matchedTerms = [];

        foreach ($knownTerms as $term) {
            if (
                strpos(
                    $lower,
                    strtolower($term)
                ) !== false
            ) {
                $matchedTerms[] = $term;
            }
        }

        if (! empty($matchedTerms)) {
            $filters['keywords'] = $matchedTerms;
        }

        /*
         * Location extraction.
         *
         * SearchJobsTool already performs location matching. These common
         * locations are only used when explicitly present in the message.
         */
        $locations = [
            'ludhiana',
            'hoshiarpur',
            'jalandhar',
            'amritsar',
            'rajpura',
            'chandigarh',
            'mohali',
            'derabassi',
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
            'kolkata',
            'visakhapatnam',
            'jaipur',
            'indore',
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
        return 'job';
    }

    /**
     * Description used by the router/agent registry.
     */
    public function description(): string
    {
        return 'Searches real jobs and helps users understand job opportunities, requirements, locations, skills and application information.';
    }
}
