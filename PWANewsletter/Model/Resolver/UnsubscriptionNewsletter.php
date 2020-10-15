<?php


namespace GoMage\PWANewsletter\Model\Resolver;


use GoMage\PWA\Helper\Data as pwaHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Phrase;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;

class UnsubscriptionNewsletter implements ResolverInterface
{


    /**
     * Subscriber factory
     *
     * @var SubscriberFactory
     */
    protected $_subscriberFactory;

    /**
     * UnsubscriptionNewsletter constructor.
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

        $email = (string)$args['input']['email'];

        try {
            $subscriber = $this->_subscriberFactory->create()->loadByEmail($email);
            if (!$subscriber->getId()
                || (int)$subscriber->getSubscriberStatus() === Subscriber::STATUS_UNSUBSCRIBED
            ) {

                return ['message' => __('This email address is already unsubscribed.')];

            }

            $subscriber->setSubscriberStatus(Subscriber::STATUS_UNSUBSCRIBED)->save();
            return ['message' => $this->getSuccessMessage()];

        } catch (LocalizedException $e) {
            $args['error'] = __('Something Wrong');
        } catch (\Exception $e) {
            $args['error'] = __('Something Wrong');
        }
        return ['error' => $args['error']];
    }


    /**
     * Get success message
     *
     * @param int $status
     * @return Phrase
     */
    private function getSuccessMessage(): Phrase
    {
        return __('You  are  unsubscription.');
    }

}