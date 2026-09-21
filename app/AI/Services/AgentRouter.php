<?php

namespace App\AI\Services;

use App\AI\Agents\CareerAgent;
use App\AI\Agents\CompanyAgent;
use App\AI\Agents\HrAgent;
use App\AI\Agents\IndustrialAgent;
use App\AI\Agents\InterviewAgent;
use App\AI\Agents\JobAgent;
use App\AI\Agents\LearningAgent;
use App\AI\Agents\NewsAgent;
use App\AI\Agents\ResumeAgent;
use App\AI\DTO\AgentRequest;
use App\AI\DTO\AgentResponse;
use Throwable;

class AgentRouter
{
    protected PromptService $promptService;

    protected AiGateway $aiGateway;

    protected ConversationService $conversationService;

    public function __construct(
        PromptService $promptService,
        AiGateway $aiGateway,
        ConversationService $conversationService
    ) {
        $this->promptService = $promptService;
        $this->aiGateway = $aiGateway;
        $this->conversationService = $conversationService;
    }

    /**
     * Route an AI request to the appropriate agent.
     *
     * Conversation handling is centralized here so every agent
     * automatically gets the same conversation persistence behavior.
     */
    public function route(
        AgentRequest $request
    ): AgentResponse {
        $conversationId = null;

        try {
            /*
             * Resolve the agent first.
             */
            $agentName = $this->resolveAgent(
                $request
            );

            $request->agent = $agentName;

            /*
             * Read an existing conversation ID from metadata.
             *
             * The controller can pass:
             *
             * metadata:
             * {
             *     "conversation_id": 123
             * }
             */
            $conversationId =
                $this->conversationIdFromRequest(
                    $request
                );

            /*
             * If no conversation exists, create one automatically.
             */
            if ($conversationId === null) {
                $conversationId =
                    $this->conversationService->start(
                        $request->userId,
                        $agentName,
                        [
                            'metadata' =>
                            $request->metadata,
                        ]
                    );
            }

            /*
             * Load previous conversation history.
             *
             * This is added to the request context so specialized
             * agents can use it when generating the next response.
             */
            if ($conversationId !== null) {
                $history =
                    $this->conversationService
                    ->historyForAi(
                        $conversationId,
                        30
                    );

                if (! empty($history)) {
                    $request->context['conversation_history'] = $history;
                }

                /*
                 * Store the current user message before calling
                 * the AI agent.
                 */
                $this->conversationService
                    ->addUserMessage(
                        $conversationId,
                        $request->message,
                        [
                            'user_id' =>
                            $request->userId,
                            'agent' =>
                            $agentName,
                        ]
                    );

                $this->conversationService->touch(
                    $conversationId
                );
            }

            /*
             * Resolve the actual agent class.
             */
            $agent = $this->resolveAgentClass(
                $agentName
            );

            /*
             * Execute specialized agent.
             */
            if (
                $agent !== null &&
                method_exists($agent, 'handle')
            ) {
                $response = $agent->handle(
                    $request
                );
            } else {
                /*
                 * Fallback to the central AI gateway.
                 */
                $response =
                    $this->aiGateway->generate(
                        $request,
                        $this->promptService
                            ->forAgent($agentName)
                    );
            }

            /*
             * Persist assistant response.
             */
            if (
                $conversationId !== null &&
                $response instanceof AgentResponse
            ) {
                $assistantMetadata = [
                    'agent' => $agentName,
                    'success' =>
                    $response->success,
                    'data' =>
                    $response->data,
                    'sources' =>
                    $response->sources,
                    'metadata' =>
                    $response->metadata,
                ];

                $this->conversationService
                    ->addAssistantMessage(
                        $conversationId,
                        $response->message,
                        $assistantMetadata
                    );

                $this->conversationService->touch(
                    $conversationId
                );

                /*
                 * Make the conversation ID available to
                 * the frontend.
                 */
                $response->metadata['conversation_id'] = $conversationId;
            }

            return $response;
        } catch (Throwable $e) {
            report($e);

            /*
             * If a conversation was created and the request
             * failed, still record the failure as an assistant
             * response so the conversation remains traceable.
             */
            if ($conversationId !== null) {
                try {
                    $this->conversationService
                        ->addAssistantMessage(
                            $conversationId,
                            'Unable to process your AI request right now.',
                            [
                                'agent' =>
                                $request->agent,
                                'success' => false,
                                'exception' =>
                                get_class($e),
                            ]
                        );

                    $this->conversationService->touch(
                        $conversationId
                    );
                } catch (Throwable $conversationException) {
                    report(
                        $conversationException
                    );
                }
            }

            $metadata = [
                'exception' => get_class($e),
            ];

            /*
             * During local development, expose the actual
             * exception so the failing AI component can
             * be identified.
             */
            if (
                (bool) config(
                    'app.debug',
                    false
                )
            ) {
                $metadata['error'] =
                    $e->getMessage();

                $metadata['file'] =
                    $e->getFile();

                $metadata['line'] =
                    $e->getLine();

                if ($conversationId !== null) {
                    $metadata['conversation_id'] = $conversationId;
                }

                return AgentResponse::failure(
                    'AI agent error: ' .
                        $e->getMessage(),
                    $request->agent,
                    [],
                    [],
                    $metadata
                );
            }

            if ($conversationId !== null) {
                $metadata['conversation_id'] = $conversationId;
            }

            /*
             * Production-safe response.
             */
            return AgentResponse::failure(
                'Unable to process your AI request right now.',
                $request->agent,
                [],
                [],
                $metadata
            );
        }
    }

    /**
     * Get conversation ID from request metadata.
     */
    protected function conversationIdFromRequest(
        AgentRequest $request
    ): ?int {
        $conversationId =
            $request->metadata['conversation_id'] ?? null;

        if (
            $conversationId === null &&
            isset($request->context['conversation_id'])
        ) {
            $conversationId =
                $request->context['conversation_id'];
        }

        if (
            $conversationId === null ||
            $conversationId === ''
        ) {
            return null;
        }

        if (
            ! is_numeric($conversationId) ||
            (int) $conversationId <= 0
        ) {
            return null;
        }

        return (int) $conversationId;
    }

    /**
     * Resolve the agent from an explicitly supplied
     * agent or user message.
     */
    public function resolveAgent(
        AgentRequest $request
    ): string {
        if ($request->hasAgent()) {
            return $this->normalizeAgent(
                $request->agent
            );
        }

        return $this->detectFromMessage(
            $request->message
        );
    }

    /**
     * Detect the most appropriate agent from the
     * user's message.
     */
    protected function detectFromMessage(
        string $message
    ): string {
        $message = strtolower(
            trim($message)
        );

        if ($message === '') {
            return 'career';
        }

        $patterns = [
            'job' => [
                'job',
                'jobs',
                'vacancy',
                'vacancies',
                'opening',
                'openings',
                'hiring',
                'work from home',
                'remote job',
                'employment',
                'apply for job',
            ],

            'resume' => [
                'resume',
                'cv',
                'curriculum vitae',
                'ats',
                'resume score',
                'resume review',
                'resume improve',
            ],

            'interview' => [
                'interview',
                'mock interview',
                'interview question',
                'interview preparation',
                'technical interview',
                'hr interview',
            ],

            'learning' => [
                'learn',
                'learning',
                'teach me',
                'explain',
                'tutorial',
                'course',
                'study',
                'practice',
                'quiz',
            ],

            'industrial' => [
                'industrial area',
                'industrial park',
                'industrial estate',
                'manufacturing',
                'factory',
                'plant',
                'cnc',
                'vmc',
                'forging',
                'foundry',
                'furnace',
                'production engineer',
                'maintenance engineer',
                'mechanical engineer',
                'industrial job',
                'factory job',
            ],

            'company' => [
                'company',
                'companies',
                'employer',
                'organization',
                'career page',
                'careers page',
                'company profile',
                'company information',
            ],

            'news' => [
                'news',
                'latest news',
                'industry news',
                'technology news',
                'market news',
                'what happened',
                'recent development',
                'announcement',
            ],

            'hr' => [
                'candidate',
                'candidates',
                'recruiter',
                'recruitment',
                'recruiting',
                'hr',
                'human resources',
                'employee onboarding',
                'onboarding',
                'screen candidates',
            ],

            'career' => [
                'career',
                'career path',
                'career change',
                'career growth',
                'skills gap',
                'skill gap',
                'what should i learn',
                'which role',
                'career advice',
            ],
        ];

        $scores = [];

        foreach (
            $patterns as $agent => $keywords
        ) {
            $score = 0;

            foreach (
                $keywords as $keyword
            ) {
                if (
                    str_contains(
                        $message,
                        $keyword
                    )
                ) {
                    $score +=
                        strlen($keyword) >= 10
                        ? 3
                        : 1;
                }
            }

            $scores[$agent] = $score;
        }

        arsort($scores);

        $bestAgent =
            array_key_first($scores);

        if (
            $bestAgent === null ||
            ($scores[$bestAgent] ?? 0) === 0
        ) {
            return 'career';
        }

        return $bestAgent;
    }

    /**
     * Resolve the actual agent class.
     */
    protected function resolveAgentClass(
        string $agent
    ) {
        switch ($this->normalizeAgent($agent)) {
            case 'job':
                return app(JobAgent::class);

            case 'career':
                return app(CareerAgent::class);

            case 'resume':
                return app(ResumeAgent::class);

            case 'interview':
                return app(InterviewAgent::class);

            case 'learning':
                return app(LearningAgent::class);

            case 'industrial':
                return app(IndustrialAgent::class);

            case 'company':
                return app(CompanyAgent::class);

            case 'news':
                return app(NewsAgent::class);

            case 'hr':
                return app(HrAgent::class);

            default:
                return null;
        }
    }

    /**
     * Normalize aliases into internal agent names.
     */
    protected function normalizeAgent(
        ?string $agent
    ): string {
        $agent = strtolower(
            trim((string) $agent)
        );

        switch ($agent) {
            case 'jobs':
            case 'job-search':
            case 'job_search':
                return 'job';

            case 'careers':
            case 'career-guidance':
            case 'career_guidance':
                return 'career';

            case 'cv':
                return 'resume';

            case 'mock-interview':
            case 'mock_interview':
                return 'interview';

            case 'learn':
                return 'learning';

            case 'industrial-career':
            case 'industrial_career':
                return 'industrial';

            case 'companies':
            case 'employer':
                return 'company';

            case 'industry':
            case 'industry-news':
            case 'industry_news':
                return 'news';

            case 'recruiter':
            case 'recruitment':
                return 'hr';

            default:
                return $agent !== ''
                    ? $agent
                    : 'career';
        }
    }

    /**
     * Return all supported agents.
     */
    public function agents(): array
    {
        return [
            'job',
            'career',
            'resume',
            'interview',
            'learning',
            'industrial',
            'company',
            'news',
            'hr',
        ];
    }

    /**
     * Check whether an agent is supported.
     */
    public function supports(
        ?string $agent
    ): bool {
        return in_array(
            $this->normalizeAgent($agent),
            $this->agents(),
            true
        );
    }
}
