<?php

namespace App\AI\Agents;

use App\AI\DTO\AgentRequest;
use App\AI\DTO\AgentResponse;
use App\AI\Services\AiGateway;
use App\AI\Services\PromptService;

class ResumeAgent
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
     * Handle resume-related conversations.
     *
     * The agent can help create, improve, review and tailor resumes.
     * It uses information supplied by the user or existing platform
     * context and must not invent employment, education or achievements.
     */
    public function handle(
        AgentRequest $request
    ): AgentResponse {
        $context = $this->buildResumeContext(
            $request
        );

        $agentRequest = new AgentRequest(
            $request->message,
            $request->userId,
            'resume',
            $context,
            $request->metadata
        );

        return $this->gateway->generate(
            $agentRequest,
            $this->prompts->resume()
        );
    }

    /**
     * Preserve existing resume/profile information supplied by the
     * application.
     */
    protected function buildResumeContext(
        AgentRequest $request
    ): array {
        $context = $request->context;

        $context['resume_request'] = [
            'message' => $request->message,
            'has_user' => $request->hasUser(),
        ];

        /*
         * Existing resume data.
         */
        if (
            isset($request->context['resume']) &&
            is_array($request->context['resume'])
        ) {
            $context['resume'] =
                $request->context['resume'];
        }

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
         * Existing certifications.
         */
        if (
            isset($request->context['certifications']) &&
            is_array($request->context['certifications'])
        ) {
            $context['certifications'] =
                $request->context['certifications'];
        }

        /*
         * Existing projects.
         */
        if (
            isset($request->context['projects']) &&
            is_array($request->context['projects'])
        ) {
            $context['projects'] =
                $request->context['projects'];
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

        return $context;
    }

    /**
     * Agent identifier.
     */
    public function name(): string
    {
        return 'resume';
    }

    /**
     * Description used by the router/agent registry.
     */
    public function description(): string
    {
        return 'Creates, reviews, improves and tailors resumes using the candidate information provided by the user or already available in the platform.';
    }
}
