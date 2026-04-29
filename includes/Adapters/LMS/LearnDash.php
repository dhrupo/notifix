<?php

namespace RTNotify\Adapters\LMS;

use RTNotify\Abstracts\AbstractLMSAdapter;

final class LearnDash extends AbstractLMSAdapter
{
    public function getSlug(): string
    {
        return 'learndash';
    }

    public function getLabel(): string
    {
        return 'LearnDash';
    }

    public function isPluginActive(): bool
    {
        return defined('LEARNDASH_VERSION');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'ld_added_course_access',
                'method'        => 'handleEnrollment',
                'accepted_args' => 2,
            ],
            [
                'hook'          => 'learndash_course_completed',
                'method'        => 'handleCompletion',
                'accepted_args' => 1,
            ],
        ];
    }

    public function handleEnrollment($userId, $courseId): void
    {
        $this->emit($this->createCourseEvent(
            'course_enrolled',
            $this->actorFromUserId(absint($userId)),
            absint($courseId),
            get_the_title((int) $courseId) ?: __('a course', 'rt-notify')
        ));
    }

    public function handleCompletion($data): void
    {
        $userId = absint($data['user']->ID ?? 0);
        $courseId = absint($data['course']->ID ?? 0);

        $this->emit($this->createCourseEvent(
            'course_completed',
            $this->actorFromUserId($userId),
            $courseId,
            get_the_title($courseId) ?: __('a course', 'rt-notify')
        ));
    }
}
