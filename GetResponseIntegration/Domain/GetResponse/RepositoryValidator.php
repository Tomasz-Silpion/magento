<?php
namespace GetResponse\GetResponseIntegration\Domain\GetResponse;

use GetResponse\GetResponseIntegration\Domain\Magento\Repository;

use GetResponse\GetResponseIntegration\Helper\Config;
use Magento\Framework\Controller\ResultFactory;
use GetResponse\GetResponseIntegration\Domain\GetResponse\Repository as GrRepository;

/**
 * Class RepositoryValidator
 * @package GetResponse\GetResponseIntegration\Domain\GetResponse
 */
class RepositoryValidator
{
    /** @var Repository */
    private $repository;

    /** @var RepositoryFactory */
    private $repositoryFactory;

    /** @var ResultFactory */
    private $resultFactory;

    /**
     * @param RepositoryFactory $repositoryFactory
     * @param Repository $repository
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        RepositoryFactory $repositoryFactory,
        Repository $repository,
        ResultFactory $resultFactory
    ) {
        $this->repository = $repository;
        $this->repositoryFactory = $repositoryFactory;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @return bool
     */
    public function validate()
    {
        try {
            return $this->validateGrRepository($this->repositoryFactory->createRepository());
        } catch (RepositoryException $e) {
            return false;
        }
    }

    /**
     * @param GrRepository $grRepository
     *
     * @return bool
     */
    public function validateGrRepository(GrRepository $grRepository)
    {
        $response = $grRepository->ping();

        if (isset($response->httpStatus) && (int)$response->httpStatus >= 400 && (int)$response->httpStatus < 500) {
            if (isset($response->code) && in_array($response->code, Config::UNAUTHORIZED_RESPONSE_CODES)) {
                $this->handleUnauthorizedApiCall();

                return false;
            }

            return true;
        }

        $this->repository->setUnauthorizedApiCallDate('');

        return true;
    }

    private function handleUnauthorizedApiCall()
    {
        $firstOccurrenceTime = $this->repository->getUnauthorizedApiCallDate();

        if (empty($firstOccurrenceTime)) {
            $this->repository->setUnauthorizedApiCallDate(time());
        } else {
            $now = time();
            if ($now - $firstOccurrenceTime > Config::DISCONNECT_DELAY) {
                $this->repository->clearDatabase();
                $this->repository->setUnauthorizedApiCallDate('');
            }
        }
    }
}
