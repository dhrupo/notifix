<?php

namespace RTNotify\Adapters\LMS;

use RTNotify\Abstracts\AbstractLMSAdapter;

final class LearnPress extends AbstractLMSAdapter
{
    public function getSlug(): string
    {
        return 'learnpress';
    }

    public function getLabel(): string
    {
        return 'LearnPress';
    }

    public function isPluginActive(): bool
    {
        return defined('LEARNPRESS_VERSION');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'learn-press/user-course-enrolled',
                'method'        => 'handleEnrollment',
                'accepted_args' => 3,
            ],
        ];
    }

    public function handleEnrollment($courseId, $userId): void
    {
        $courseId = absint($courseId);
        $userId = absint($userId);

        $this->emit($this->createCourseEvent(
            'course_enrolled',
            $this->actorFromUserId($userId),
            $courseId,
            get_the_title($courseId) ?: __('a course', 'rt-notify')
        ));
    }
}
