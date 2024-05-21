<?php

namespace Drupal\conference_room_reservation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ConferenceRoomController extends ControllerBase {

  public function view() {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'conference_room')
      ->condition('status', 1);
    $nids = $query->execute();
    $rooms = \Drupal\node\Entity\Node::loadMultiple($nids);

    $rows = [];
    foreach ($rooms as $room) {
      $rows[] = [
        'data' => [
          $room->getTitle(),
          $room->field_capacity->value,
          $room->field_availability->value,
          \Drupal::l('Book', \Drupal\Core\Url::fromRoute('conference_room_reservation.book', ['room_id' => $room->id()])),
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

    if ($request->isMethod('post')) {
      $date = $request->request->get('date');
      // Add booking logic here (e.g., saving the booking to the database).

      \Drupal::messenger()->addStatus('Room booked successfully for ' . $date);
      return new RedirectResponse('/conference-room');
    }

    $form = \Drupal::formBuilder()->getForm('Drupal\conference_room_reservation\Form\BookingForm', $room);

    return [
      '#theme' => 'conference_room_book',
      '#room' => $room,
      '#form' => $form,
    ];
  }
}
