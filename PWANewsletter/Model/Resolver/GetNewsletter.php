<?php


namespace GoMage\PWANewsletter\Model\Resolver;


use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;

class GetNewsletter implements ResolverInterface
{
    /**
     * @var SubscriberFactory
     */
    protected $_subscriberFactory;

    /**
     * GetNewsletter constructor.
     * @param SubscriberFactory $subscriberFactory
     */
    public function __construct(
        SubscriberFactory $subscriberFactory
    )
    {
        $this->_subscriberFactory = $subscriberFactory;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     * @throws GraphQlInputException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {

        if (empty($args)) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }

        if ($email = (string)$args['input']['email']) {
            $subscriber = $this->_subscriberFactory->create()->loadByEmail($email);
            if ($subscriber->getId()
                && (int)$subscriber->getSubscriberStatus() === Subscriber::STATUS_SUBSCRIBED
            ) {
                return ['subscribed' => true];
            }

        }

        return ['subscribed' => false];
    }

}