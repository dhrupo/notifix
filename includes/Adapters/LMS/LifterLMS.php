<?php

namespace RTNotify\Adapters\LMS;

use RTNotify\Abstracts\AbstractLMSAdapter;

final class LifterLMS extends AbstractLMSAdapter
{
    public function getSlug(): string
    {
        return 'lifterlms';
    }

    public function getLabel(): string
    {
        return 'LifterLMS';
    }

    public function isPluginActive(): bool
    {
        return defined('LLMS_VERSION');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'llms_user_enrolled_in_course',
                'method'        => 'handleEnrollment',
                'accepted_args' => 2,
            ],
            [
                'hook'          => 'llms_user_completed_course',
                'method'        => 'handleCompletion',
                'accepted_args' => 2,
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

    public function handleCompletion($userId, $courseId): void
    {
        $this->emit($this->createCourseEvent(
            'course_completed',
            $this->actorFromUserId(absint($userId)),
            absint($courseId),
            get_the_title((int) $courseId) ?: __('a course', 'rt-notify')
        ));
    }
}
