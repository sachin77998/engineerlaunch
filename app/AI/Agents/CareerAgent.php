<?php

namespace App\AI\Agents;

use App\AI\DTO\AgentRequest;
use App\AI\DTO\AgentResponse;
use App\AI\Services\AiGateway;
use App\AI\Services\PromptService;

class CareerAgent
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
     * Handle career-related conversations.
     *
     * CareerAgent focuses on career planning, skill development,
     * job-search strategy and career decisions. It does not invent
     * platform data.
     */
    public function handle(
        AgentRequest $request
    ): AgentResponse {
        $context = $this->buildCareerContext(
            $request
        );

        $agentRequest = new AgentRequest(
            $request->message,
            $request->userId,
            'career',
            $context,
            $request->metadata
        );

        return $this->gateway->generate(
            $agentRequest,
            $this->prompts->career()
        );
    }

    /**
     * Build structured context for the career AI.
     *
     * Existing platform context can be supplied by the controller,
     * frontend or future career-profile tool.
     */
    protected function buildCareerContext(
        AgentRequest $request
    ): array {
        $context = $request->context;

        /*
         * Give the AI useful metadata without inventing
         * candidate information.
         */
        $context['career_request'] = [
            'message' => $request->message,
            'has_user' => $request->hasUser(),
        ];

        /*
         * If the application already provides a career profile,
         * preserve it exactly.
         */
        if (
            isset($request->context['career_profile']) &&
            is_array($request->context['career_profile'])
        ) {
            $context['career_profile'] =
                $request->context['career_profile'];
        }

        /*
         * If skills already exist in the request context, preserve them.
         */
        if (
            isset($request->context['skills']) &&
            is_array($request->context['skills'])
        ) {
            $context['skills'] =
                $request->context['skills'];
        }

        /*
         * If experience already exists, preserve it.
         */
        if (
            isset($request->context['experience']) &&
            is_array($request->context['experience'])
        ) {
            $context['experience'] =
                $request->context['experience'];
        }

        /*
         * If education already exists, preserve it.
         */
        if (
            isset($request->context['education']) &&
            is_array($request->context['education'])
        ) {
            $context['education'] =
                $request->context['education'];
        }

        /*
         * If the application already supplied recommended jobs,
         * preserve those real results.
         */
        if (
            isset($request->context['jobs']) &&
            is_array($request->context['jobs'])
        ) {
            $context['jobs'] =
                $request->context['jobs'];
        }

        return $context;
    }

    /**
     * Agent identifier.
     */
    public function name(): string
    {
        return 'career';
    }

    /**
     * Description used by the router/agent registry.
     */
    public function description(): string
    {
        return 'Helps users plan careers, identify skill gaps, choose learning paths, understand roles and improve their job-search strategy.';
    }
}
