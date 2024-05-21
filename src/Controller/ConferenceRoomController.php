<?php

namespace Drupal\conference_room_reservation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Link;
use Drupal\Core\Url;

class ConferenceRoomController extends ControllerBase {

  public function view() {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'conference_room')
      ->condition('status', 1)
      ->accessCheck(TRUE); // Ensure access check is explicitly set

    $nids = $query->execute();
    $rooms = \Drupal\node\Entity\Node::loadMultiple($nids);

    $rows = [];
    foreach ($rooms as $room) {
      $link = Link::fromTextAndUrl('Book', Url::fromRoute('conference_room_reservation.book', ['room_id' => $room->id()]))->toString();
      $rows[] = [
        'data' => [
          $room->getTitle(),
          $room->field_capacity->value,
          $room->field_availability->value,
          $link,
        ],
      ];
    }

    $header = ['Room', 'Capacity', 'Availability', 'Actions'];
    $build = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

  public function book($room_id, Request $request) {
    $room = \Drupal\node\Entity\Node::load($room_id);

    // Check if the form is submitted
    if ($request->isMethod('post')) {
      // Get booking date from the form submission
      $date = $request->request->get('date');
      
      // Save the booking information
      // This is a simplified example; in a real application, you'd save this in a custom entity or a similar data structure.
      \Drupal::messenger()->addStatus('Room booked successfully for ' . $date);

      // Redirect back to the conference room list
      return new RedirectResponse('/conference-room');
    }

    // Load the booking form
    $form = \Drupal::formBuilder()->getForm('Drupal\conference_room_reservation\Form\BookingPageForm');

    return [
      '#theme' => 'conference_room_book_page',
      '#room' => $room,
      '#form' => $form,
    ];
  }


  public function bookingPage() {
    // Load the booking form without a specific room
    $form = \Drupal::formBuilder()->getForm('Drupal\conference_room_reservation\Form\BookingPageForm');

    return [
      '#theme' => 'conference_room_book_page',
      '#form' => $form,
    ];
  }
}
