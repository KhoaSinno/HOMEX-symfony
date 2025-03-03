<?php
namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

        // Kiểm tra nếu lỗi là 404 Not Found
        if ($exception instanceof NotFoundHttpException) {
            $response = new Response(
                $this->twig->render('errors/error404.html.twig'),
                Response::HTTP_NOT_FOUND
            );
            $event->setResponse($response);
        }
    }
}
