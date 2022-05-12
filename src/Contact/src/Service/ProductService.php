<?php

declare(strict_types=1);

namespace Frontend\Contact\Service;

use Frontend\Contact\Entity\Cart;
use Frontend\Contact\Entity\Message;
use Frontend\Contact\Entity\Product;
use Frontend\Contact\Repository\CartRepository;
use Frontend\Contact\Repository\MessageRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Dot\AnnotatedServices\Annotation\Inject;
use Dot\Mail\Exception\MailException;
use Dot\Mail\Service\MailService;
use Frontend\Contact\Repository\ProductRepository;
use Frontend\User\Entity\User;
use Mezzio\Template\TemplateRendererInterface;

/**
 * Class ProductService
 * @package Frontend\Contact\Service
 */
class ProductService implements ProductServiceInterface
{
    /** @var ProductRepository $repository */
    protected $repository;

    /** @var CartRepository $cartRepository */
    protected $cartRepository;

    /** @var MailService $mailService */
    protected $mailService;

    /** @var TemplateRendererInterface $templateRenderer */
    protected $templateRenderer;

    /** @var array $config */
    protected $config;

    /**
     * MessageService constructor.
     * @param EntityManager $entityManager
     * @param MailService $mailService
     * @param TemplateRendererInterface $templateRenderer
     * @param array $config
     *
     * @Inject({EntityManager::class, MailService::class,
     * TemplateRendererInterface::class, "config"})
     */
    public function __construct(
        EntityManager $entityManager,
        MailService $mailService,
        TemplateRendererInterface $templateRenderer,
        array $config = []
    ) {
        $this->repository = $entityManager->getRepository(Product::class);
        $this->cartRepository = $entityManager->getRepository(Cart::class);
        $this->mailService = $mailService;
        $this->templateRenderer = $templateRenderer;
        $this->config = $config;
    }

    /**
     * @return ProductRepository
     */
    public function getRepository(): ProductRepository
    {
        return $this->repository;
    }

    /**
     * @return CartRepository
     */
    public function getCartRepository(): CartRepository
    {
        return $this->cartRepository;
    }

    /**
     * @param $data
     * @return void
     */
    public function processProduct($uuid, User $user)
    {
        /** @var Product $data */
        $data = $this->getRepository()->findOneBy(['uuid' => $uuid['product'] ]);

        $cart = new Cart(
            $data->getProduct(),
            $data->getPrice(),
            $data->getDescription(),
            $data->getImage()
        );
        $cart->setUser($user);

        $this->getCartRepository()->saveCart($cart);
    }

    public function getProcessedProducts()
    {
        $products = $this->getRepository()->getProducts();

        $result = [];
        foreach ($products as $key => $product) {
            $result[$key]['uuid'] = $product->getUuid()->toString();
            $result[$key]['product'] = $product->getProduct();
            $result[$key]['price'] = $product->getPrice();
            $result[$key]['description'] = $product->getDescription();
            $result[$key]['image'] = $product->getImage();
        }
        return $result;
    }
}
