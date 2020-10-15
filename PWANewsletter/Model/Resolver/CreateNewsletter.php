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
use Magento\Customer\Api\AccountManagementInterface as CustomerAccountManagement;
use Magento\Customer\Model\Session;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Newsletter\Model\SubscriberFactory;

class CreateNewsletter implements ResolverInterface
{


    /**
     * Customer session
     *
     * @var Session
     */
    protected $_customerSession;

    /**
     * Subscriber factory
     *
     * @var SubscriberFactory
     */
    protected $_subscriberFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;


    /**
     * @var pwaHelper
     */
    protected $pwaHelper;

    /**
     * @var EmailValidator
     */
    protected $emailValidator;

    /**
     * @var CustomerAccountManagement
     */
    protected $customerAccountManagement;

    /**
     * CreateNewsletter constructor.
     * @param pwaHelper $pwaHelper
     * @param SubscriberFactory $subscriberFactory
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param CustomerAccountManagement $customerAccountManagement
     * @param EmailValidator $emailValidator
     */
    public function __construct(
        pwaHelper $pwaHelper,
        SubscriberFactory $subscriberFactory,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        CustomerAccountManagement $customerAccountManagement,
        EmailValidator $emailValidator
    )
    {
        $this->customerAccountManagement = $customerAccountManagement;
        $this->emailValidator = $emailValidator;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_customerSession = $customerSession;
        $this->_storeManager = $storeManager;
        $this->pwaHelper = $pwaHelper;
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
        $args = $this->validateEmailFormat($email, $args);
        $args = $this->validateGuestSubscription($args);
        $args = $this->validateEmailAvailable($email, $args);
        $args['error'] = false;
        try {
            $subscriber = $this->_subscriberFactory->create()->loadByEmail($email);
            if ($subscriber->getId()
                && (int)$subscriber->getSubscriberStatus() === Subscriber::STATUS_SUBSCRIBED
            ) {
                return ['message' => __('This email address is already subscribed.')];
            }
            if (!$args['error']) {
                $status = (int)$this->_subscriberFactory->create()->subscribe($email);
                return ['message' => $this->getSuccessMessage($status)];
            }
        } catch (LocalizedException $e) {
            $args['error'] = __('Something Wrong');
        } catch (\Exception $e) {
            $args['error'] = __('Something Wrong');
        }
        return ['error' => $args['error']];
    }

    /**
     * Validates that the email address isn't being used by a different account.
     *
     * @param string $email
     * @return void
     * @throws LocalizedException
     */
    protected function validateEmailAvailable($email, $args)
    {
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        if ($this->_customerSession->isLoggedIn()
            && ($this->_customerSession->getCustomerDataObject()->getEmail() !== $email
                && !$this->customerAccountManagement->isEmailAvailable($email, $websiteId))
        ) {
            $args['error'] = __('This email address is already assigned to another user.');
        }
    }

    /**
     * Validates that if the current user is a guest, that they can subscribe to a newsletter.
     *
     * @return void
     * @throws LocalizedException
     */
    protected function validateGuestSubscription($args)
    {
        if ($this->pwaHelper->getScopeData(
                null,
                Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG
            ) != 1
            && !$this->_customerSession->isLoggedIn()
        ) {
            $args['error'] = __(
                'Sorry, but the administrator denied subscription for guests.'
            );
        }
    }

    /**
     * Validates the format of the email address
     *
     * @param string $email
     * @return void
     * @throws LocalizedException
     */
    protected function validateEmailFormat($email)
    {
        if (!$this->emailValidator->isValid($email)) {
            $args['error'] = __('Please enter a valid email address.');
        }
    }


    /**
     * Get success message
     *
     * @param int $status
     * @return Phrase
     */
    private function getSuccessMessage(int $status): Phrase
    {
        if ($status === Subscriber::STATUS_NOT_ACTIVE) {
            return __('The confirmation request has been sent.');
        }

        return __('Thank you for your subscription.');
    }

}