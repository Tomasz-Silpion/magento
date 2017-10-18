<?php
namespace GetResponse\GetResponseIntegration\Block;

use GetResponse\GetResponseIntegration\Controller\Adminhtml\AccessValidator;
use GetResponse\GetResponseIntegration\Domain\GetResponse\AccountFactory;
use GetResponse\GetResponseIntegration\Domain\GetResponse\RepositoryFactory;
use Magento\Framework\View\Element\Template\Context;
use GetResponse\GetResponseIntegration\Domain\Magento\Repository;
use Magento\Framework\View\Element\Template;

/**
 * Class Settings
 * @package GetResponse\GetResponseIntegration\Block
 */
class Settings extends Template
{
    /** @var Repository */
    private $repository;

    /** @var RepositoryFactory */
    private $repositoryFactory;

    /**
     * @param Context $context
     * @param Repository $repository
     * @param RepositoryFactory $repositoryFactory
     */
    public function __construct(
        Context $context,
        Repository $repository,
        RepositoryFactory $repositoryFactory
    )
    {
        parent::__construct($context);
        $this->repository = $repository;
        $this->repositoryFactory = $repositoryFactory;
    }

    /**
     * @return mixed
     */
    public function getCustomers()
    {
        return $this->repository->getFullCustomersDetails();
    }

    /**
     * @return mixed
     */
    public function getSettings()
    {
        return $this->repository->getSettings();
    }

    /**
     * @return mixed
     */
    public function getWebformSettings()
    {
        return $this->repository->getWebformSettings();
    }

    /**
     * @return mixed
     */
    public function getAccountInfo()
    {
        return AccountFactory::buildFromRepository($this->repository->getAccountInfo());
    }

    /**
     * @return array
     */
    public function getAllFormsFromGr()
    {
        $settings = $this->getSettings();
        $forms = [];

        if (!isset($settings['api_key'])) {
            return $forms;
        }

        $grRepository = $this->repositoryFactory->createRepository($settings['api_key'], $settings['api_url'], $settings['api_domain']);

        $newForms = $grRepository->getForms(['query' => ['status' => 'enabled']]);
        foreach ($newForms as $form) {
            if ($form->status == 'published') {
                $forms['forms'][] = $form;
            }
        }
        $oldWebforms = $grRepository->getWebForms();
        foreach ($oldWebforms as $webform) {
            if ($webform->status == 'enabled') {
                $forms['webforms'][] = $webform;
            }
        }

        return $forms;
    }

    /**
     * @return mixed
     */
    public function getActiveCustoms()
    {
        return $this->repository->getActiveCustoms();
    }

    /**
     * @return bool
     */
    public function getLastPostedApiKey()
    {
        $data = $this->getRequest()->getPostValue();
        if (!empty($data)) {
            if (isset($data['getresponse_api_key'])) {
                return $data['getresponse_api_key'];
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function getHiddenApiKey()
    {
        $settings = $this->repository->getSettings();

        if (!isset($settings['api_key'])) {
            return '';
        }

        $apiKey = $settings['api_key'];
        return strlen($apiKey) > 0 ? str_repeat("*", strlen($apiKey) - 6) . substr($apiKey, -6) : '';
    }

    /**
     * @return int
     */
    public function getLastPostedApiAccount()
    {
        $data = $this->getRequest()->getPostValue();
        if (!empty($data['getresponse_360_account']) && 1 == $data['getresponse_360_account']) {
            return $data['getresponse_360_account'];
        }
        return 0;
    }

    /**
     * @return bool
     */
    public function getLastPostedApiUrl()
    {
        $data = $this->getRequest()->getPostValue();
        if (!empty($data['getresponse_api_url'])) {
            return $data['getresponse_api_url'];
        }
        return false;
    }

    /**
     * @return bool
     */
    public function getLastPostedApiDomain()
    {
        $data = $this->getRequest()->getPostValue();
        if (!empty($data['getresponse_api_domain'])) {
            return $data['getresponse_api_domain'];
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getAutomations()
    {
        return $this->repository->getRules();
    }

    /**
     * @return bool
     */
    public function isConnectedToGetResponse()
    {
        return (new AccessValidator($this->repository))->isConnectedToGetResponse();
    }
}
