<?php

namespace Wedo\GrinderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Wedo\GrinderBundle\Entity\Image;

class DefaultController extends Controller
{
    /**
     * Landing page action
     *
     * @return Response A Response instance
     */
    public function indexAction()
    {
        return $this->render('WedoGrinderBundle:Default:index.html.twig');
    }

    /**
     * Search action - Calls Google Custom Search with the query supplied
     *
     * @return Response A Response instance
     */
    public function searchAction()
    {
        $request = $this->getRequest();

        $query = urlencode($request->query->get('q'));

        //Call API with cURL
        if ($query) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/customsearch/v1?key=AIzaSyDefK5AWmxAqOOylzjGdeoQoMugCWGs6vY&cx=018365642268131947363%3Aey_owyw_v2o&q=$query&searchType=image");
            ob_start();
            curl_exec($ch);
            $c = ob_get_contents();
            ob_end_clean();
            curl_close($ch);

            $result = json_decode($c, true);

            return $this->render(
                'WedoGrinderBundle:Default:search-results.html.twig', 
                [
                    'items' => $result["items"]
                ]
            );
        } else {
            //bzzzzt! Nothing to display
            return new Response();
        }
    }

    /**
     * Select action - After search results select the beans to save in db
     *
     * @return Response A Response instance
     */
    public function selectAction()
    {
        $memcached = $this->get('memcached');
        $request = $this->getRequest();

        if ($request && $request->get('image')) {
            $em = $this->getDoctrine()->getManager();
            foreach ($request->get('image') as $key => $value) {
                $image = new Image();
                $image->setLink($value);
                $em->persist($image);
            }
            $em->flush();
        }

        $repository = $this->getDoctrine()->getRepository('WedoGrinderBundle:Image');
        $items = $repository->findAll();

        return $this->render('WedoGrinderBundle:Default:select.html.twig', [ 'items' => $items ]);
    }

    /**
     * Add action - Finally, select some beans, save to memcache
     *
     * @return Response A Response instance
     */
    public function addAction()
    {
        $memcached = $this->get('memcached');
        $request = $this->getRequest();

        if ($request && $request->get('quantity')) {
            //Save as an object in memcache
            //as it doesn't guarantee to return
            //a list of all existing keys across servers
            $store = $memcached->get('store');
            if ($store) {
                $store = json_decode($store, true);
            }
            $store[$request->get('id')] = $request->get('quantity');
            $memcached->set('store', json_encode($store));
        }

        //return something meaningful
        return new JsonResponse(array("done" => 1));
    }

    /**
     * Get action - Retrieve beans from memcache
     *
     * @return Response A Response instance
     */
    public function getAction()
    {
        $memcached = $this->get('memcached');
        $store = $memcached->get('store');
        if ($store) {
            $store = json_decode($store, true);
            $repository = $this->getDoctrine()->getRepository('WedoGrinderBundle:Image');
            $toReturn = array();

            foreach ($store as $id => $quantity) {
                $query = $repository->createQueryBuilder('i')
                            ->where('i.id = :id')
                            ->setParameter('id', $id)
                            ->getQuery();

                try {
                    $image = $query->getSingleResult();
                    $toReturn[$id] = [
                        'id'    => $id,
                        'quantity'  => $quantity,
                        'img'       => $image->getLink()
                    ];
                } catch (\Doctrine\ORM\NoResultException $e) {

                }
            }
        }

        return new JsonResponse($toReturn);
    }

    /**
     * Grind action - Done with coffee? Now get back to work!
     *
     * @return Response A Response instance
     */
    public function grindAction()
    {
        $memcached = $this->get('memcached');
        $memcached->delete('store');

        //Ugly, there must be a better way
        $connection = $this->getDoctrine()->getManager()->getConnection();
        $connection->executeUpdate("TRUNCATE Image");

        return $this->render('WedoGrinderBundle:Default:grind.html.twig');
    }
}
