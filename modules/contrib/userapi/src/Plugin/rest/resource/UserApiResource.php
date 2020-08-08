<?php

namespace Drupal\userapi\Plugin\rest\resource;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Database\Database;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "custom_rest_endpoint_get",
 *   label = @Translation("User Api resource GET"),
 *   uri_paths = {
 *     "canonical" = "/userapi/custom_rest_endpoint_get/{number_ident}"
 *   }
 * )
 */
class UserApiResource extends ResourceBase
{

  protected $currentUser;

  /**
   * UserApiResource constructor.
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param array $serializer_formats
   * @param LoggerInterface $logger
   * @param AccountProxyInterface $current_user
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user)
  {
    parent::__construct ($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
  }

  /**
   * @param ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @return \Drupal\Core\Plugin\ContainerFactoryPluginInterface|ResourceBase|UserApiResource
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new Static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter ('serializer.formats'),
      $container->get ('logger.factory')->get('apiDrupal'),
      $container->get ('current_user')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @param null $number_ident
   * @return ResourceResponse
   *   The HTTP response object.
   *
   */
  public function get($number_ident = NULL) {

    if (!$this->currentUser->hasPermission ('access content')){
      throw new AccessDeniedHttpException();
    }
    $select = Database::getConnection ()
      ->select ('userapi','user')
      ->fields ('user',array(
        'first_name',
        'last_name',
        'email',
        'number_identification',
        'number_phone',
        'date_born'));

    $select->condition ('number_identification ',$number_ident, '=');
    $user_api = $select->execute ()->fetchAll (\PDO::FETCH_ASSOC);
    if(count ($user_api)>0){
      $response = $user_api;
    }else{
      $response['status'] = 200;
      $response['message'] = "User do not find, try with others number identification!";
    }
    return new ResourceResponse($response);
  }

}
