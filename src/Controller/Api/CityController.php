<?php

namespace App\Controller\Api;

use App\Annotation\ReverseProxy;
use App\Entity\City;
use App\Invalidator\TagsInvalidator;
use App\SearchRepository\CityElasticaRepository;
use FOS\ElasticaBundle\Manager\RepositoryManagerInterface;
use FOS\HttpCache\ResponseTagger;
use FOS\HttpCacheBundle\Configuration\Tag;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class CityController extends AbstractController
{
    const MAX_RESULTS = 7;

    /**
     * @Route("/villes", name="app_api_city")
     * @ReverseProxy(expires="1 year")
     * @Tag("autocomplete-city")
     *
     * @return JsonResponse
     */
    public function cityAutocompleteAction(ResponseTagger $responseTagger, Request $request, PaginatorInterface $paginator, RepositoryManagerInterface $repositoryManager)
    {
        $term = \trim($request->get('q'));
        if (!$term) {
            $results = [];
        } else {
            /** @var CityElasticaRepository $repo */
            $repo = $repositoryManager->getRepository(City::class);
            $results = $repo->findWithSearch($term);
            $results = $paginator->paginate($results, 1, self::MAX_RESULTS);
        }

        $jsonResults = [];
        foreach ($results as $result) {
            /** @var City $result */
            $responseTagger->addTags([TagsInvalidator::getCityTag($result)]);
            $jsonResults[] = [
                'slug' => $result->getSlug(),
                'name' => $result->getFullName(),
            ];
        }

        return new JsonResponse($jsonResults);
    }
}