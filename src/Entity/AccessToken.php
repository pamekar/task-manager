<?php
namespace App\Entity;
use FOS\OAuthServerBundle\Entity\AccessToken as BaseAccessToken;
use Doctrine\ORM\Mapping as ORM;
/**
 * @ORM\Entity
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(name="token",
 *          column=@ORM\Column(
 *              name     = "token",
 *              length   = 191,
 *              unique   = true
 *          )
 *      )
 * })
 */
class AccessToken extends BaseAccessToken
{
  /**
   * @ORM\Id
   * @ORM\Column(type="integer")
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  protected $id;
  /**
   * @ORM\ManyToOne(targetEntity="Client")
   * @ORM\JoinColumn(nullable=false)
   */
  protected $client;
  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\User")
   */
  protected $user;
}