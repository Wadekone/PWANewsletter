<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace GoMage\PWANewsletter\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

/**
 * Orders data reslover
 */
class Config implements ResolverInterface
{
    const XML_CONFIG = 'gomage_pwa_newsletter';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ValueFactory
     */
    private $valueFactory;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ValueFactory $valueFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ValueFactory $valueFactory
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->valueFactory = $valueFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {
        try {
            return $this->getData();
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()));
        } catch (LocalizedException $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()));
        }
    }

    /**
     *
     * @param int $context
     * @return array
     * @throws NoSuchEntityException|LocalizedException
     */
    private function getData()
    {
        try {
            $data = [];
            foreach ($this->scopeConfig->getValue(self::XML_CONFIG) as $key => $config) {
                foreach ($config as $sub_key => $item) {
                    $config_key = $key . '_' . $sub_key;
                    $data[$config_key] =  $item;
                }
            }
            return isset($data) ? $data : [];
        } catch (NoSuchEntityException $e) {
            return [];
        } catch (LocalizedException $e) {
            throw new NoSuchEntityException(__($e->getMessage()));
        }
    }
}
