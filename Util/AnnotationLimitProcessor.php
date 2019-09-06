<?php

namespace Noxlogic\RateLimitBundle\Util;

use Closure;
use Noxlogic\RateLimitBundle\LimitProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class AnnotationLimitProcessor implements LimitProcessorInterface
{
    /**
     * @var LimitProcessorInterface
     */
    private $fallback;

    public function __construct(LimitProcessorInterface $fallback)
    {
        $this->fallback = $fallback;
    }

    public function getRateLimit(Request $request)
    {
        $annotations = $request->attributes->get('_x-rate-limit', array());

        if (!$annotations) {
            return $this->fallback->getRateLimit($request);
        }

        $best_match = null;
        foreach ($annotations as $annotation) {
            // cast methods to array, even method holds a string
            $methods = is_array($annotation->getMethods()) ? $annotation->getMethods() : array($annotation->getMethods());

            if (in_array($request->getMethod(), $methods)) {
                $best_match = $annotation;
            }

            // Only match "default" annotation when we don't have a best match
            if (count($annotation->getMethods()) == 0 && $best_match == null) {
                $best_match = $annotation;
            }
        }

        return $best_match;
    }

    public function getRateLimitAlias(Request $request, callable $controller)
    {
        $annotations = $request->attributes->get('_x-rate-limit', array());

        if (!$annotations) {
            return $this->fallback->getRateLimitAlias($request, $controller);
        }

        if (($route = $request->attributes->get('_route'))) {
            return $route;
        }

        if (is_string($controller) && false !== strpos($controller, '::')) {
            $controller = explode('::', $controller);
        }

        if (is_array($controller)) {
            return str_replace('\\', '.', is_string($controller[0]) ? $controller[0] : get_class($controller[0])) . '.' . $controller[1];
        }

        if ($controller instanceof Closure) {
            return 'closure';
        }

        if (is_object($controller)) {
            return str_replace('\\', '.', get_class($controller));
        }

        return 'other';
    }
}
