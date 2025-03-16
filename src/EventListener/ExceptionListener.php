<?php
namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Twig\Environment;

class ExceptionListener
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        
        if ($exception instanceof NotFoundHttpException) {
            // Xử lý lỗi 404
            $response = new Response(
                $this->twig->render('errors/error404.html.twig'),
                Response::HTTP_NOT_FOUND
            );
            $event->setResponse($response);
        } elseif ($exception instanceof AccessDeniedHttpException) {
            // Xử lý lỗi 403
            $response = new Response(
                $this->twig->render('errors/error403.html.twig'),
                Response::HTTP_FORBIDDEN
            );
            $event->setResponse($response);
        }
    }
}
