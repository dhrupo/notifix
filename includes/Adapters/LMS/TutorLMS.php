<?php

namespace RTNotify\Adapters\LMS;

use RTNotify\Abstracts\AbstractLMSAdapter;

final class TutorLMS extends AbstractLMSAdapter
{
    public function getSlug(): string
    {
        return 'tutorlms';
    }

    public function getLabel(): string
    {
        return 'Tutor LMS';
    }

    public function isPluginActive(): bool
    {
        return defined('TUTOR_VERSION');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'tutor_after_enroll',
                'method'        => 'handleEnrollment',
                'accepted_args' => 1,
            ],
        ];
    }

    public function handleEnrollment($enrollmentId): void
    {
        if (! function_exists('tutor_utils')) {
            return;
        }

        $utils = tutor_utils();
        $courseId = absint($utils->get_course_id_by_enrollment($enrollmentId));
        $studentId = absint($utils->get_enrolled_user_id($enrollmentId));

        $this->emit($this->createCourseEvent(
            'course_enrolled',
            $this->actorFromUserId($studentId),
            $courseId,
            get_the_title($courseId) ?: __('a course', 'rt-notify')
        ));
    }
}
