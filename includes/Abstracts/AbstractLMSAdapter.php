<?php

namespace RTNotify\Abstracts;

abstract class AbstractLMSAdapter extends AbstractHookAdapter
{
    protected function createCourseEvent(string $type, array $actor, int $courseId, string $courseTitle, array $meta = []): array
    {
        return $this->createEvent($type, [
            'actor'  => $actor,
            'object' => $this->buildObject($courseId, 'course', $courseTitle),
            'meta'   => $meta,
        ]);
    }
}
