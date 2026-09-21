<?php

namespace App\AI\Agents;

use App\AI\DTO\AgentRequest;
use App\AI\DTO\AgentResponse;
use App\AI\Services\AiGateway;
use App\AI\Services\PromptService;

class LearningAgent
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
     * Handle learning-related conversations.
     */
    public function handle(
        AgentRequest $request
    ): AgentResponse {
        $context = $this->buildLearningContext(
            $request
        );

        $agentRequest = new AgentRequest(
            $request->message,
            $request->userId,
            'learning',
            $context,
            $request->metadata
        );

        return $this->gateway->generate(
            $agentRequest,
            $this->prompts->learning()
        );
    }

    /**
     * Preserve existing learning-related information supplied
     * by the application.
     */
    protected function buildLearningContext(
        AgentRequest $request
    ): array {
        $context = $request->context;

        $context['learning_request'] = [
            'message' => $request->message,
            'has_user' => $request->hasUser(),
        ];

        /*
         * Existing learner profile.
         */
        if (
            isset($request->context['learning_profile']) &&
            is_array($request->context['learning_profile'])
        ) {
            $context['learning_profile'] =
                $request->context['learning_profile'];
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
         * Existing target skills.
         */
        if (
            isset($request->context['target_skills']) &&
            is_array($request->context['target_skills'])
        ) {
            $context['target_skills'] =
                $request->context['target_skills'];
        }

        /*
         * Existing learning path.
         */
        if (
            isset($request->context['learning_path']) &&
            is_array($request->context['learning_path'])
        ) {
            $context['learning_path'] =
                $request->context['learning_path'];
        }

        /*
         * Existing courses/topics from the platform.
         */
        if (
            isset($request->context['courses']) &&
            is_array($request->context['courses'])
        ) {
            $context['courses'] =
                $request->context['courses'];
        }

        if (
            isset($request->context['topics']) &&
            is_array($request->context['topics'])
        ) {
            $context['topics'] =
                $request->context['topics'];
        }

        /*
         * Existing assessments/results.
         */
        if (
            isset($request->context['assessments']) &&
            is_array($request->context['assessments'])
        ) {
            $context['assessments'] =
                $request->context['assessments'];
        }

        if (
            isset($request->context['quiz_results']) &&
            is_array($request->context['quiz_results'])
        ) {
            $context['quiz_results'] =
                $request->context['quiz_results'];
        }

        /*
         * Existing job target.
         */
        if (
            isset($request->context['target_job']) &&
            is_array($request->context['target_job'])
        ) {
            $context['target_job'] =
                $request->context['target_job'];
        }

        /*
         * Existing role target.
         */
        if (
            isset($request->context['target_role']) &&
            is_array($request->context['target_role'])
        ) {
            $context['target_role'] =
                $request->context['target_role'];
        }

        return $context;
    }

    /**
     * Agent identifier.
     */
    public function name(): string
    {
        return 'learning';
    }

    /**
     * Description used by the router/agent registry.
     */
    public function description(): string
    {
        return 'Helps users learn technologies and professional skills, create learning paths, identify skill gaps and prepare for career goals using the platform learning content.';
    }
}
