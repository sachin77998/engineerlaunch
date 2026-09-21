<?php

namespace App\AI\Agents;

use App\AI\DTO\AgentRequest;
use App\AI\DTO\AgentResponse;
use App\AI\Services\AiGateway;
use App\AI\Services\IndustrialDirectoryService;
use App\AI\Services\PromptService;
use App\AI\Tools\SearchIndustrialAreasTool;
use App\AI\Tools\SearchRolesTool;
use App\AI\Tools\SearchSkillsTool;

class IndustrialAgent
{
    protected AiGateway $gateway;

    protected PromptService $prompts;

    protected IndustrialDirectoryService $directory;

    protected SearchIndustrialAreasTool $searchAreas;

    protected SearchRolesTool $searchRoles;

    protected SearchSkillsTool $searchSkills;

    public function __construct(
        AiGateway $gateway,
        PromptService $prompts,
        IndustrialDirectoryService $directory,
        SearchIndustrialAreasTool $searchAreas,
        SearchRolesTool $searchRoles,
        SearchSkillsTool $searchSkills
    ) {
        $this->gateway = $gateway;
        $this->prompts = $prompts;
        $this->directory = $directory;
        $this->searchAreas = $searchAreas;
        $this->searchRoles = $searchRoles;
        $this->searchSkills = $searchSkills;
    }

    /**
     * Handle industrial-area and industrial-career conversations.
     *
     * Examples:
     *
     * - "Show industrial areas near Ludhiana"
     * - "What companies are in Sanand?"
     * - "Find CNC operator roles"
     * - "What skills are required for die engineer?"
     * - "Show manufacturing jobs in Haridwar"
     */
    public function handle(
        AgentRequest $request
    ): AgentResponse {
        $context = $this->buildIndustrialContext(
            $request
        );

        $agentRequest = new AgentRequest(
            $request->message,
            $request->userId,
            'industrial',
            $context,
            $request->metadata
        );

        return $this->gateway->generate(
            $agentRequest,
            $this->prompts->industrial()
        );
    }

    /**
     * Build real industrial data context before calling the AI.
     */
    protected function buildIndustrialContext(
        AgentRequest $request
    ): array {
        $context = $request->context;

        $message = trim(
            $request->message
        );

        /*
         * Always preserve the user's original request.
         */
        $context['industrial_request'] = [
            'message' => $message,
            'has_user' => $request->hasUser(),
        ];

        /*
         * Search industrial areas when the request appears to concern
         * industrial geography.
         */
        if ($this->isAreaRequest($message)) {
            $areaResults = $this->searchAreas->execute([
                'search' => $message,
                'limit' => 10,
            ]);

            $context['industrial_areas'] =
                $areaResults['industrial_areas'];
        }

        /*
         * Search roles when the request appears to concern an industrial
         * job role.
         */
        if ($this->isRoleRequest($message)) {
            $roleResults = $this->searchRoles->execute([
                'search' => $message,
                'limit' => 15,
            ]);

            $context['industrial_roles'] =
                $roleResults['roles'];
        }

        /*
         * Search skills when the user asks about skills, qualifications
         * or requirements.
         */
        if ($this->isSkillRequest($message)) {
            $skillResults = $this->searchSkills->execute([
                'search' => $message,
                'limit' => 20,
            ]);

            $context['industrial_skills'] =
                $skillResults['skills'];
        }

        /*
         * If an area ID was explicitly supplied by the existing
         * application, use the existing IndustrialDirectoryService.
         */
        if (
            isset($request->context['area_id']) &&
            is_numeric($request->context['area_id'])
        ) {
            $areaId = (int) $request->context['area_id'];

            $context['industrial_area'] =
                $this->directory->area($areaId);

            $context['industrial_area_summary'] =
                $this->directory->areaSummary($areaId);

            $context['industrial_hierarchy'] =
                $this->directory->hierarchy($areaId);
        }

        /*
         * If a company ID is already known, preserve the existing
         * directory/company intelligence.
         */
        if (
            isset($request->context['company_id']) &&
            is_numeric($request->context['company_id'])
        ) {
            $companyId = (int) $request->context['company_id'];

            $context['industrial_company'] =
                $this->directory->company($companyId);

            $context['industrial_company_summary'] =
                $this->directory->companySummary($companyId);
        }

        /*
         * Preserve existing live-job results if another part of the
         * application has already retrieved them.
         */
        if (
            isset($request->context['live_jobs']) &&
            is_array($request->context['live_jobs'])
        ) {
            $context['live_jobs'] =
                $request->context['live_jobs'];
        }

        /*
         * Preserve existing industrial intelligence.
         */
        if (
            isset($request->context['industrial_intelligence']) &&
            is_array($request->context['industrial_intelligence'])
        ) {
            $context['industrial_intelligence'] =
                $request->context['industrial_intelligence'];
        }

        return $context;
    }

    /**
     * Determine whether the request concerns industrial areas.
     */
    protected function isAreaRequest(
        string $message
    ): bool {
        $text = strtolower($message);

        $keywords = [
            'industrial area',
            'industrial areas',
            'industrial estate',
            'industrial estates',
            'industrial park',
            'industrial parks',
            'industrial zone',
            'industrial zones',
            'manufacturing hub',
            'manufacturing hubs',
            'industrial hub',
            'industrial hubs',
            'sidcul',
            'midc',
            'gidc',
            'riico',
            'apiic',
            'psiec',
            'focal point',
            'cluster',
            'clusters',
        ];

        return $this->containsAny(
            $text,
            $keywords
        );
    }

    /**
     * Determine whether the request concerns industrial roles.
     */
    protected function isRoleRequest(
        string $message
    ): bool {
        $text = strtolower($message);

        $keywords = [
            'role',
            'roles',
            'job role',
            'job roles',
            'operator',
            'engineer',
            'technician',
            'fitter',
            'helper',
            'supervisor',
            'maintenance',
            'production',
            'quality',
            'cnc',
            'vmc',
            'die maker',
            'die engineer',
            'tool room',
            'furnace',
            'foundry',
            'forging',
            'warehouse',
            'logistics',
            'store',
            'purchase',
            'mechanical',
            'electrical',
            'manufacturing',
        ];

        return $this->containsAny(
            $text,
            $keywords
        );
    }

    /**
     * Determine whether the request concerns skills or qualifications.
     */
    protected function isSkillRequest(
        string $message
    ): bool {
        $text = strtolower($message);

        $keywords = [
            'skill',
            'skills',
            'qualification',
            'qualifications',
            'requirement',
            'requirements',
            'learn',
            'training',
            'experience',
            'knowledge',
            'what should i learn',
            'what do i need',
        ];

        return $this->containsAny(
            $text,
            $keywords
        );
    }

    /**
     * Check whether any keyword occurs in the message.
     */
    protected function containsAny(
        string $text,
        array $keywords
    ): bool {
        foreach ($keywords as $keyword) {
            if (
                strpos(
                    $text,
                    strtolower($keyword)
                ) !== false
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Agent identifier.
     */
    public function name(): string
    {
        return 'industrial';
    }

    /**
     * Description used by the router/agent registry.
     */
    public function description(): string
    {
        return 'Provides industrial-area, manufacturing-company, industrial-role, skill and industrial-career intelligence using the platform directory and real database records.';
    }
}
