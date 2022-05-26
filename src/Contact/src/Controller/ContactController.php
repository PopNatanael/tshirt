<?php

namespace Frontend\Contact\Controller;

use Dot\AnnotatedServices\Annotation\Inject;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Dot\Controller\AbstractActionController;
use Dot\FlashMessenger\FlashMessenger;
use Dot\Mail\Exception\MailException;
use Fig\Http\Message\RequestMethodInterface;
use Frontend\Contact\Entity\Product;
use Frontend\Contact\Form\ContactForm;
use Frontend\Contact\Service\MessageService;
use Frontend\Contact\Service\ProductServiceInterface;
use Frontend\Plugin\FormsPlugin;
use Frontend\User\Entity\User;
use Frontend\User\Form\UploadAvatarForm;
use Frontend\User\Service\UserServiceInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use mysql_xdevapi\Exception;
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
        $userCart = $this->productService->getCartRepository()->getUserCartItems($user);
        $totalPrice = $this->productService->getCartRepository()->getTotalPrice($user);
        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            echo "<script>window.location.href='productList';</script>";

            $data = $request->getParsedBody();
            if (isset($data['action']) && $data['action'] === 'emptycart') {
                $this->productService->getCartRepository()->emptyUserCart($user);
            }
            if (isset($data['product']) && !is_null($data['product'])) {
                $this->productService->processProduct($data, $user);
            }
            if (isset($data['removedProductFromCart'])) {
                $deletedProduct = $this->productService->getCartRepository()->find($data['removedProductFromCart']);
                $this->productService->getCartRepository()->deleteUserCartProduct($deletedProduct);
            }
            if (isset($data['checkoutCart'])) {
                return new RedirectResponse($this->router->generateUri("contact", ['action' => 'cartCheckout']));
            }
            if (isset($_POST['deleteCart'])) {
                $this->productService->getCartRepository()->emptyUserCart($user);
            }
        }
        return new HtmlResponse($this->template->render('contact::products', [
            'products' => $allProducts,
            'userCart' => $userCart,
            'totalPrice' => $totalPrice
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

    public function addProductAction(): ResponseInterface
    {
        $request = $this->getRequest();
        $identity = $this->authenticationService->getIdentity();
        $user = $this->userService->findByUuid($identity->getUuid());
        $form = new UploadAvatarForm();

        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $file = $this->request->getUploadedFiles()['avatar']['file'] ?? '';
            var_dump();
            file_put_contents( dirname(__FILE__).'/../../../../public/images/image.png', $file);
            if ($file->getSize() === 0) {
                $this->messenger->addWarning('Please select a file for upload.', 'profile-avatar');
                return new RedirectResponse($this->router->generateUri(
                    "contact",
                    ['action' => 'addProduct']
                ));
            }
            try {
                $this->userService->updateUser($user, ['avatar' => $file]);
            } catch (Exception $e) {
                $this->messenger->addError('Something went wrong updating your profile image!', 'profile-avatar');
                return new RedirectResponse($this->router->generateUri(
                    "account",
                    ['action' => 'avatar']
                ));
            }
            if (isset($_POST['productTitle']) && isset($_POST['avatar']['image']) && isset($_POST['productDescription']) && isset($_POST['productPrice'])) {
                $product = new Product($_POST['productTitle'], $_POST['productPrice'], $_POST['productDescription'], $_POST['avatar']['image']);
                $this->productService->getRepository()->saveProduct($product);
            }
        }
        return new HtmlResponse($this->template->render('contact::add-product', [ 'form' => $form, 'user' =>$user
        ]));
    }

    public function cartCheckoutAction(): ResponseInterface
    {
        $request = $this->getRequest();
        $identity = $this->authenticationService->getIdentity();
        $user = $this->userService->findByUuid($identity->getUuid());
        $totalPrice = $this->productService->getCartRepository()->getTotalPrice($user);
        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {

        }
            $userCart = $this->productService->getCartRepository()->getUserCartItems($user);
        return new HtmlResponse($this->template->render('contact::cart-checkout', [
            'products' => $userCart,
            'totalPrice' => $totalPrice
        ]));
    }
}
