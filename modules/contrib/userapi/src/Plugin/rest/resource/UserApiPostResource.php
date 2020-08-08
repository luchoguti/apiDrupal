<?php

namespace Drupal\userapi\Plugin\rest\resource;

use Drupal\Core\Database\Database;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a resource to post nodes.
 *
 * @RestResource(
 *   id = "custom_rest_endpoint_post",
 *   label = @Translation("User Api resource POST"),
 *   uri_paths = {
 *     "create" = "/userapi/custom_rest_endpoint_post"
 *   }
 * )
 */
class UserApiPostResource extends ResourceBase
{

  use StringTranslationTrait;

  protected $currentUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param LoggerInterface $logger
   * @param AccountProxyInterface $current_user
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user)
  {

    parent::__construct ($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter ('serializer.formats'),
      $container->get ('logger.factory')->get ('apiDrupal'),
      $container->get ('current_user')
    );
  }

  /**
   * Responds to POST requests.
   *
   * Creates a new node.
   *
   * @param mixed $data
   *   Data to create the node.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post($data)
  {
    $sql = Database::getConnection ();
    $date = new \DateTime();
    $valid_date = false;
    if(isset($data['date_born'])){
      $date_two = new \DateTime($data['date_born']);
      $diffYear=$date->diff($date_two);
      if(($diffYear->format("%y")) > 18){
        $valid_date = true;
      }
    }
    if($valid_date){
      $data = array_merge($data, array("created"=>\Drupal::time()->getRequestTime()));
      $result= $sql->insert('userapi')->fields ($data)->execute ();
      if($result){
        $result = "The User was created successfully user id ".$result;
      }
    }else{
      $result = "The user cannot be registered because he is not of legal age!";
    }
    $response['status'] = 200;
    $response['message'] = $result;
    return new ResourceResponse($response);

  }

}
