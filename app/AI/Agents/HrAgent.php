<?php

namespace App\AI\Agents;

use App\AI\DTO\AgentRequest;
use App\AI\DTO\AgentResponse;
use App\AI\Services\AiGateway;
use App\AI\Services\PromptService;

class HrAgent
{
    protected AiGateway $gateway;

    protected PromptService $prompts;

    public function __construct(
        AiGateway $gateway,
        PromptService $prompts
    ) {
        $this->gateway = $gateway;
        $this->prompts = $prompts;
    }

    /**
     * Handle HR and employer-related conversations.
     *
     * Existing HR data supplied by the application is preserved and
     * passed to the AI. The agent does not invent candidates, employees,
     * verification results, jobs or HR records.
     */
    public function handle(
        AgentRequest $request
    ): AgentResponse {
        $context = $this->buildHrContext(
            $request
        );

        $agentRequest = new AgentRequest(
            $request->message,
            $request->userId,
            'hr',
            $context,
            $request->metadata
        );

        return $this->gateway->generate(
            $agentRequest,
            $this->prompts->hr()
        );
    }

    /**
     * Preserve existing HR/employer context.
     */
    protected function buildHrContext(
        AgentRequest $request
    ): array {
        $context = $request->context;

        $context['hr_request'] = [
            'message' => $request->message,
            'has_user' => $request->hasUser(),
        ];

        /*
         * Existing employer/company information.
         */
        if (
            isset($request->context['company']) &&
            is_array($request->context['company'])
        ) {
            $context['company'] =
                $request->context['company'];
        }

        if (
            isset($request->context['employer']) &&
            is_array($request->context['employer'])
        ) {
            $context['employer'] =
                $request->context['employer'];
        }

        /*
         * Existing recruiter information.
         */
        if (
            isset($request->context['recruiter']) &&
            is_array($request->context['recruiter'])
        ) {
            $context['recruiter'] =
                $request->context['recruiter'];
        }

        /*
         * Existing job information.
         */
        if (
            isset($request->context['job']) &&
            is_array($request->context['job'])
        ) {
            $context['job'] =
                $request->context['job'];
        }

        /*
         * Existing candidate information.
         */
        if (
            isset($request->context['candidate']) &&
            is_array($request->context['candidate'])
        ) {
            $context['candidate'] =
                $request->context['candidate'];
        }

        if (
            isset($request->context['candidate_profile']) &&
            is_array($request->context['candidate_profile'])
        ) {
            $context['candidate_profile'] =
                $request->context['candidate_profile'];
        }

        /*
         * Existing candidate search results.
         */
        if (
            isset($request->context['candidates']) &&
            is_array($request->context['candidates'])
        ) {
            $context['candidates'] =
                $request->context['candidates'];
        }

        /*
         * Existing applications.
         */
        if (
            isset($request->context['applications']) &&
            is_array($request->context['applications'])
        ) {
            $context['applications'] =
                $request->context['applications'];
        }

        /*
         * Existing employee information.
         */
        if (
            isset($request->context['employee']) &&
            is_array($request->context['employee'])
        ) {
            $context['employee'] =
                $request->context['employee'];
        }

        if (
            isset($request->context['employees']) &&
            is_array($request->context['employees'])
        ) {
            $context['employees'] =
                $request->context['employees'];
        }

        /*
         * Existing onboarding information.
         */
        if (
            isset($request->context['onboarding']) &&
            is_array($request->context['onboarding'])
        ) {
            $context['onboarding'] =
                $request->context['onboarding'];
        }

        /*
         * Existing verification information.
         */
        if (
            isset($request->context['verification']) &&
            is_array($request->context['verification'])
        ) {
            $context['verification'] =
                $request->context['verification'];
        }

        /*
         * Existing interview/assessment information.
         */
        if (
            isset($request->context['assessment']) &&
            is_array($request->context['assessment'])
        ) {
            $context['assessment'] =
                $request->context['assessment'];
        }

        if (
            isset($request->context['interview']) &&
            is_array($request->context['interview'])
        ) {
            $context['interview'] =
                $request->context['interview'];
        }

        /*
         * Existing recruitment workflow information.
         */
        if (
            isset($request->context['recruitment']) &&
            is_array($request->context['recruitment'])
        ) {
            $context['recruitment'] =
                $request->context['recruitment'];
        }

        return $context;
    }

    /**
     * Agent identifier.
     */
    public function name(): string
    {
        return 'hr';
    }

    /**
     * Description used by the router/agent registry.
     */
    public function description(): string
    {
        return 'Helps employers and HR teams with hiring, candidate management, recruitment workflows, interview processes, verification, onboarding and employee lifecycle information using existing platform data.';
    }
}
