<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;

class ActionFragmentRenderer implements FragmentRendererInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var ControllerResolverInterface
     */
    private $controllerResolver;
    /**
     * @var ArgumentResolverInterface
     */
    private $argumentResolver;

    /**
     * Main constructor.
     */
    public function __construct(ContainerInterface $container, ControllerResolverInterface $controllerResolver, ArgumentResolverInterface $argumentResolver)
    {
        $this->container = $container;
        $this->controllerResolver = $controllerResolver;
        $this->argumentResolver = $argumentResolver;
    }

    public function render($uri, Request $request, array $options = [])
    {
        if (!$uri instanceof ControllerReference) {
            throw new \InvalidArgumentException('ControllerReference not found');
        }

        $uri->attributes['_controller'] = $uri->controller;
        $uri->query['_path'] = http_build_query($uri->attributes, '', '&');

        $attributes = $request->attributes->all();
        $attributes = array_merge($attributes, $uri->attributes);
        $fakeRequest = $request->duplicate($uri->query, null, $attributes);

        // controller resolver
        // load controller
        if (false === $controller = $this->controllerResolver->getController($fakeRequest)) {
            //            throw new NotFoundHttpException('Unable to find the controller');
            throw new NotFoundHttpException('Not found. (B221)');
        }
        // controller arguments
        $arguments = $this->argumentResolver->getArguments($fakeRequest, $controller);
        // call controller
        $response = call_user_func_array($controller, $arguments);

        if (!$response instanceof Response) {
            $msg = 'The controller must return a response.';

            // the user may have forgotten to return something
            if (null === $response) {
                $msg .= ' Did you forget to add a return statement somewhere in your controller?';
            }

            throw new \LogicException($msg);
        }

        return $response;
    }

    public function getName()
    {
        return 'action';
    }
}
