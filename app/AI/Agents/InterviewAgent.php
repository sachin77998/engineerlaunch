<?php

namespace App\AI\Agents;

use App\AI\DTO\AgentRequest;
use App\AI\DTO\AgentResponse;
use App\AI\Services\AiGateway;
use App\AI\Services\PromptService;

class InterviewAgent
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
     * Handle interview-preparation conversations.
     */
    public function handle(
        AgentRequest $request
    ): AgentResponse {
        $context = $this->buildInterviewContext(
            $request
        );

        $agentRequest = new AgentRequest(
            $request->message,
            $request->userId,
            'interview',
            $context,
            $request->metadata
        );

        return $this->gateway->generate(
            $agentRequest,
            $this->prompts->interview()
        );
    }

    /**
     * Preserve interview-related information already supplied
     * by the application.
     */
    protected function buildInterviewContext(
        AgentRequest $request
    ): array {
        $context = $request->context;

        $context['interview_request'] = [
            'message' => $request->message,
            'has_user' => $request->hasUser(),
        ];

        /*
         * Existing candidate profile.
         */
        if (
            isset($request->context['candidate_profile']) &&
            is_array($request->context['candidate_profile'])
        ) {
            $context['candidate_profile'] =
                $request->context['candidate_profile'];
        }

        /*
         * Existing career profile.
         */
        if (
            isset($request->context['career_profile']) &&
            is_array($request->context['career_profile'])
        ) {
            $context['career_profile'] =
                $request->context['career_profile'];
        }

        /*
         * Existing resume information.
         */
        if (
            isset($request->context['resume']) &&
            is_array($request->context['resume'])
        ) {
            $context['resume'] =
                $request->context['resume'];
        }

        /*
         * Existing skills.
         */
        if (
            isset($request->context['skills']) &&
            is_array($request->context['skills'])
        ) {
            $context['skills'] =
                $request->context['skills'];
        }

        /*
         * Existing education.
         */
        if (
            isset($request->context['education']) &&
            is_array($request->context['education'])
        ) {
            $context['education'] =
                $request->context['education'];
        }

        /*
         * Existing experience.
         */
        if (
            isset($request->context['experience']) &&
            is_array($request->context['experience'])
        ) {
            $context['experience'] =
                $request->context['experience'];
        }

        /*
         * Existing target job.
         */
        if (
            isset($request->context['target_job']) &&
            is_array($request->context['target_job'])
        ) {
            $context['target_job'] =
                $request->context['target_job'];
        }

        /*
         * Existing interview configuration.
         *
         * This allows a future interview UI to pass:
         * - interview type
         * - difficulty
         * - number of questions
         * - role
         * - technology
         * - topic
         */
        if (
            isset($request->context['interview']) &&
            is_array($request->context['interview'])
        ) {
            $context['interview'] =
                $request->context['interview'];
        }

        /*
         * Existing job description.
         */
        if (
            isset($request->context['job']) &&
            is_array($request->context['job'])
        ) {
            $context['job'] =
                $request->context['job'];
        }

        return $context;
    }

    /**
     * Agent identifier.
     */
    public function name(): string
    {
        return 'interview';
    }

    /**
     * Description used by the router/agent registry.
     */
    public function description(): string
    {
        return 'Provides interview preparation, technical questions, HR questions, mock interviews, answer evaluation and role-specific interview guidance.';
    }
}
