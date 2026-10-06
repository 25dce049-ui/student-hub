-- ============================================================
--  Practical 8 – StudentHub  |  Advanced Extension
--  File   : stored_procedures.sql
--  Tools  : MySQL 8.x / phpMyAdmin
--
--  Three stored procedures:
--   1. sp_register_student   – safe registration with seat check
--   2. sp_event_summary      – aggregate report per event
--   3. sp_student_history    – full registration history for one student
-- ============================================================

USE studenthub;

-- Drop if they exist so the file is re-runnable
DROP PROCEDURE IF EXISTS sp_register_student;
DROP PROCEDURE IF EXISTS sp_event_summary;
DROP PROCEDURE IF EXISTS sp_student_history;

DELIMITER $$

-- ============================================================
--  1. sp_register_student
--
--  Safely registers a student for an event.
--  Checks:
--    a) Event exists
--    b) Available seats (max_seats - confirmed registrations > 0)
--    c) Student not already registered
--  OUT param `out_message` returns a human-readable result.
-- ============================================================
CREATE PROCEDURE sp_register_student(
    IN  p_student_id INT UNSIGNED,
    IN  p_event_id   INT UNSIGNED,
    IN  p_status     ENUM('Pending','Confirmed','Cancelled'),
    OUT out_code     TINYINT,        -- 0=success, 1=full, 2=duplicate, 3=not_found
    OUT out_message  VARCHAR(255)
)
BEGIN
    DECLARE v_max_seats       SMALLINT UNSIGNED DEFAULT 0;
    DECLARE v_taken           INT      DEFAULT 0;
    DECLARE v_already_exists  INT      DEFAULT 0;

    -- a) Check event exists and get max_seats
    SELECT max_seats INTO v_max_seats
    FROM   events
    WHERE  event_id = p_event_id;

    IF v_max_seats IS NULL THEN
        SET out_code    = 3;
        SET out_message = 'Event not found.';
    ELSE
        -- b) Count confirmed/pending registrations
        SELECT COUNT(*) INTO v_taken
        FROM   registrations
        WHERE  event_id = p_event_id
          AND  status   != 'Cancelled';

        IF v_taken >= v_max_seats THEN
            SET out_code    = 1;
            SET out_message = CONCAT('Event is full (', v_max_seats, ' seats taken).');
        ELSE
            -- c) Check for duplicate
            SELECT COUNT(*) INTO v_already_exists
            FROM   registrations
            WHERE  student_id = p_student_id
              AND  event_id   = p_event_id;

            IF v_already_exists > 0 THEN
                SET out_code    = 2;
                SET out_message = 'Student is already registered for this event.';
            ELSE
                -- All checks passed – insert
                INSERT INTO registrations (student_id, event_id, status)
                VALUES (p_student_id, p_event_id, p_status);

                SET out_code    = 0;
                SET out_message = CONCAT('Registration successful. Seats remaining: ',
                                         (v_max_seats - v_taken - 1));
            END IF;
        END IF;
    END IF;
END$$


-- ============================================================
--  2. sp_event_summary
--
--  Returns a summary report for every event:
--    event title, date, category, max seats,
--    confirmed count, pending count, cancelled count,
--    and available seats.
-- ============================================================
CREATE PROCEDURE sp_event_summary()
BEGIN
    SELECT
        e.event_id,
        e.title,
        e.event_date,
        e.category,
        e.venue,
        e.max_seats,
        SUM(CASE WHEN r.status = 'Confirmed'  THEN 1 ELSE 0 END) AS confirmed_count,
        SUM(CASE WHEN r.status = 'Pending'    THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN r.status = 'Cancelled'  THEN 1 ELSE 0 END) AS cancelled_count,
        (e.max_seats
            - SUM(CASE WHEN r.status != 'Cancelled' THEN 1 ELSE 0 END)
        )                                                           AS available_seats
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.event_id
    GROUP BY e.event_id, e.title, e.event_date, e.category, e.venue, e.max_seats
    ORDER BY e.event_date ASC;
END$$


-- ============================================================
--  3. sp_student_history
--
--  Returns the complete event registration history for a
--  given student, including event details and status.
-- ============================================================
CREATE PROCEDURE sp_student_history(
    IN p_student_id INT UNSIGNED
)
BEGIN
    SELECT
        s.full_name,
        s.email,
        s.course,
        s.year,
        e.title        AS event_title,
        e.category,
        e.event_date,
        e.venue,
        r.status,
        r.registered_at
    FROM   students     s
    JOIN   registrations r ON r.student_id = s.student_id
    JOIN   events        e ON e.event_id   = r.event_id
    WHERE  s.student_id = p_student_id
    ORDER BY r.registered_at DESC;
END$$

DELIMITER ;


-- ============================================================
--  Usage Examples (run in phpMyAdmin SQL tab)
-- ============================================================

-- 1. Register student #1 for event #5
-- CALL sp_register_student(1, 5, 'Confirmed', @code, @msg);
-- SELECT @code AS result_code, @msg AS result_message;

-- 2. Full event summary
-- CALL sp_event_summary();

-- 3. History for student #3
-- CALL sp_student_history(3);
