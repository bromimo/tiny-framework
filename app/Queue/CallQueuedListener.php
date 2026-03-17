<?php

namespace App\Queue;

use App\Facades\App;
use App\Abstracts\Job;

/** Bridge: оборачивает event listener с ShouldQueue в Job для очереди. */
class CallQueuedListener extends Job
{
    /** @var string FQCN класса listener. */
    private readonly string $listenerClass;

    /** @var object Объект события. */
    private readonly object $event;

    /** @param string $listenerClass FQCN listener-а.
     *  @param object $event         Объект события.
     */
    public function __construct(string $listenerClass, object $event)
    {
        $this->listenerClass = $listenerClass;
        $this->event = $event;

        $this->copyListenerProperties($listenerClass);
    }

    /** Выполнить listener с событием. */
    public function handle(): void
    {
        $listener = App::make($this->listenerClass);
        ($listener)($this->event);
    }

    /** Скопировать queue-настройки из класса listener (если заданы).
     * @param string $listenerClass FQCN listener-а.
     */
    private function copyListenerProperties(string $listenerClass): void
    {
        $properties = ['timeout', 'tries', 'backoff', 'queue'];

        foreach ($properties as $prop) {
            if (property_exists($listenerClass, $prop)) {
                $reflection = new \ReflectionProperty($listenerClass, $prop);
                if ($reflection->hasDefaultValue()) {
                    $this->$prop = $reflection->getDefaultValue();
                }
            }
        }
    }
}
