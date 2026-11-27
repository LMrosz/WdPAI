<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database;
use App\Exception\ApiException;
use App\Exception\ReservationException;
use App\Model\Reservation;
use App\Model\TastingSession;
use App\Repository\PostgresReservationRepository;
use App\Repository\PostgresTastingRepository;
use App\Repository\ReservationRepositoryInterface;
use App\Repository\TastingRepositoryInterface;
use App\Service\Session;
use PDO;

/**
 * API rezerwacji degustacji dla strony /tastings (public/js/reservations.js):
 *  - GET    /api/tasting-sessions   przyszłe terminy z wolnymi miejscami (publiczne),
 *  - POST   /api/reservations       rezerwacja miejsc, ciało {"sessionId": 3, "seats": 2},
 *  - DELETE /api/reservations/{id}  anulowanie własnej rezerwacji.
 *
 * Rezerwacja i anulowanie wymagają zalogowania (401) i tokenu CSRF w nagłówku X-CSRF-Token (403).
 * Reguły limitów są w PostgresReservationRepository (transakcja z blokadą terminu).
 */
final class ReservationApiController extends AppController
{
    /** Powód odmowy rezerwacji => kod HTTP. Konflikty z aktualnym stanem to 409, złe dane 422. */
    private const STATUS_BY_REASON = [
        'session_not_found' => 404,
        'reservation_not_found' => 404,
        'session_past' => 409,
        'not_enough_seats' => 409,
        'already_booked' => 409,
        'group_too_large' => 422,
    ];

    private const MAX_SEATS = 12;

    private ?PDO $pdo = null;
    private ?TastingRepositoryInterface $tastings = null;
    private ?ReservationRepositoryInterface $reservations = null;

    /** GET /api/tasting-sessions – terminy od najbliższego; zalogowany widzi też własne rezerwacje. */
    public function sessions(): void
    {
        $userId = Session::user()['id'] ?? null;
        $mine = $userId === null ? [] : $this->reservations()->findConfirmedByUser((int) $userId);

        $this->json([
            'sessions' => array_map(
                fn (TastingSession $session): array => $this->presentSession($session, $mine[$session->id] ?? null),
                $this->tastings()->findUpcomingSessions(),
            ),
        ]);
    }

    /** POST /api/reservations – rezerwuje miejsca; 201 z rezerwacją i odświeżonym terminem. */
    public function create(): void
    {
        $user = $this->requireUser();
        $this->requireValidCsrfHeader();

        $body = $this->jsonBody();
        $sessionId = $body['sessionId'] ?? null;
        $seats = $body['seats'] ?? null;

        if (!is_int($sessionId) || $sessionId < 1 || !is_int($seats) || $seats < 1 || $seats > self::MAX_SEATS) {
            throw new ApiException(422, 'invalid_seats');
        }

        try {
            $reservation = $this->reservations()->book($user->id, $sessionId, $seats);
        } catch (ReservationException $exception) {
            throw $this->toApiException($exception);
        }

        $this->json([
            'reservation' => $this->presentReservation($reservation),
            'session' => $this->presentCurrentSession($sessionId, $reservation),
        ], 201);
    }

    /** DELETE /api/reservations/{id} – anuluje rezerwację zalogowanej osoby; cudza to 404. */
    public function cancel(string $id): void
    {
        $user = $this->requireUser();
        $this->requireValidCsrfHeader();

        try {
            $reservation = $this->reservations()->cancel($this->idFromPath($id), $user->id);
        } catch (ReservationException $exception) {
            throw $this->toApiException($exception);
        }

        $this->json([
            'reservation' => $this->presentReservation($reservation),
            'session' => $this->presentCurrentSession($reservation->sessionId, null),
        ]);
    }

    private function toApiException(ReservationException $exception): ApiException
    {
        return new ApiException(self::STATUS_BY_REASON[$exception->reason()] ?? 409, $exception->reason());
    }

    /** Termin po zmianie, z aktualną liczbą wolnych miejsc (czytaną z widoku już po zapisie). */
    private function presentCurrentSession(int $sessionId, ?Reservation $mine): array
    {
        $session = $this->tastings()->findSession($sessionId)
            ?? throw new ApiException(404, 'session_not_found');

        return $this->presentSession($session, $mine);
    }

    /** @return array<string, mixed> */
    private function presentSession(TastingSession $session, ?Reservation $mine): array
    {
        return [
            'id' => $session->id,
            'tastingId' => $session->tastingId,
            'tastingName' => $session->tastingName,
            'startsAt' => $session->startsAt->format(\DATE_ATOM),
            'label' => $session->startsAtLabel(),
            'seatsTotal' => $session->seatsTotal,
            'seatsFree' => $session->seatsFree(),
            'maxSeats' => $session->maxSeats,
            'myReservation' => $mine === null ? null : ['id' => $mine->id, 'seats' => $mine->seats],
        ];
    }

    /** @return array<string, mixed> */
    private function presentReservation(Reservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'sessionId' => $reservation->sessionId,
            'seats' => $reservation->seats,
            'status' => $reservation->status,
        ];
    }

    /** Jedno połączenie na żądanie, wspólne dla obu repozytoriów. */
    private function pdo(): PDO
    {
        return $this->pdo ??= (new Database())->connect();
    }

    private function tastings(): TastingRepositoryInterface
    {
        return $this->tastings ??= new PostgresTastingRepository($this->pdo());
    }

    private function reservations(): ReservationRepositoryInterface
    {
        return $this->reservations ??= new PostgresReservationRepository($this->pdo());
    }
}
