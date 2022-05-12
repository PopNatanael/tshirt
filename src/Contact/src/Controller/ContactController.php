<?php

namespace Frontend\Contact\Controller;

use Dot\AnnotatedServices\Annotation\Inject;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Dot\Controller\AbstractActionController;
use Dot\FlashMessenger\FlashMessenger;
use Dot\Mail\Exception\MailException;
use Fig\Http\Message\RequestMethodInterface;
use Frontend\Contact\Form\ContactForm;
use Frontend\Contact\Service\MessageService;
use Frontend\Contact\Service\ProductServiceInterface;
use Frontend\Plugin\FormsPlugin;
use Frontend\User\Entity\User;
use Frontend\User\Service\UserServiceInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;

class ContactController extends AbstractActionController
{
    /** @var RouterInterface $router */
    protected RouterInterface $router;

    /** @var TemplateRendererInterface $template */
    protected TemplateRendererInterface $template;

    /** @var MessageService $messageService */
    protected MessageService $messageService;

    /** @var AuthenticationServiceInterface $authenticationService */
    protected AuthenticationServiceInterface $authenticationService;

    /** @var ProductServiceInterface $productService */
    protected ProductServiceInterface $productService;


    /** @var UserServiceInterface $userService */
    protected UserServiceInterface $userService;

    /** @var FlashMessenger $messenger */
    protected FlashMessenger $messenger;

    /** @var FormsPlugin $forms */
    protected FormsPlugin $forms;

    /** @var array $config */
    protected $config;

    /**
     * UserController constructor.
     * @param MessageService $messageService
     * @param RouterInterface $router
     * @param TemplateRendererInterface $template
     * @param AuthenticationService $authenticationService
     * @param ProductServiceInterface $productService
     * @param UserServiceInterface $userService
     * @param FlashMessenger $messenger
     * @param FormsPlugin $forms
     * @Inject({
     *     MessageService::class,
     *     RouterInterface::class,
     *     TemplateRendererInterface::class,
     *     AuthenticationService::class,
     *     ProductServiceInterface::class,
     *     UserServiceInterface::class,
     *     FlashMessenger::class,
     *     FormsPlugin::class,
     *     "config"
     *     })
     */
    public function __construct(
        MessageService $messageService,
        RouterInterface $router,
        TemplateRendererInterface $template,
        AuthenticationService $authenticationService,
        ProductServiceInterface $productService,
        UserServiceInterface $userService,
        FlashMessenger $messenger,
        FormsPlugin $forms,
        array $config = []
    ) {
        $this->messageService = $messageService;
        $this->router = $router;
        $this->template = $template;
        $this->authenticationService = $authenticationService;
        $this->productService = $productService;
        $this->messenger = $messenger;
        $this->userService =$userService;
        $this->forms = $forms;
        $this->config = $config;
    }

    /**
     * @return ResponseInterface
     * @throws MailException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function formAction(): ResponseInterface
    {
        $form = new ContactForm();
        $request = $this->getRequest();

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $data = $request->getParsedBody();
            //check recaptcha
            if (isset($data['g-recaptcha-response'])) {
                if (!$this->messageService->recaptchaIsValid($data['g-recaptcha-response'])) {
                    unset($data['g-recaptcha-response']);
                    $this->messenger->addError('Wrong recaptcha');
                    return new RedirectResponse($request->getUri(), 303);
                }
            } else {
                $this->messenger->addError('Missing recaptcha');
                return new RedirectResponse($request->getUri(), 303);
            }

            $data['subject'] = 'DotKernel Message ' . date("Y-m-d H:i:s");
            $form->setData($data);
            if ($form->isValid()) {
                $dataForm = $form->getData();

                $result = $this->messageService->processMessage($dataForm);

                if ($result) {
                    return new HtmlResponse($this->template->render('contact::thank-you'));
                } else {
                    $this->messenger->addError('Something went wrong. Please try again later!');
                    return new RedirectResponse($request->getUri(), 303);
                }
            } else {
                $this->messenger->addError($this->forms->getMessages($form));
                return new RedirectResponse($request->getUri(), 303);
            }
        }

        return new HtmlResponse($this->template->render('contact::contact-form', [
            'form' => $form,
            'recaptchaSiteKey' => $this->config['recaptcha']['siteKey']
        ]));
    }

    public function productListAction(): ResponseInterface
    {
        $request = $this->getRequest();

        $identity = $this->authenticationService->getIdentity();

        /** @var User $user */
        $user = $this->userService->findByUuid($identity->getUuid());

        $allProducts = $this->productService->getProcessedProducts();

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $data = $request->getParsedBody();
            $this->productService->processProduct($data, $user);
        }
        return new HtmlResponse($this->template->render('contact::products', [
            'products' => $allProducts
        ]));
    }

    public function cartAction(): ResponseInterface
    {
        if (isset($_POST['products'])) {
            $cart = $_POST['products'];
            $products = [];
            foreach ($cart as $product) {
                $products = $this->productService->getRepository()->find($product);
            }
        }

        return new HtmlResponse($this->template->render('contact::cart', [
            'products' => $products
        ]));
    }
}
