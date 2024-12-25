DELIMITER //

-- Lösche existierende Trigger
DROP TRIGGER IF EXISTS before_insert_led_mapping//
DROP TRIGGER IF EXISTS before_update_led_mapping//

-- Erstelle neue Trigger
CREATE TRIGGER before_insert_led_mapping
BEFORE INSERT ON led_mapping
FOR EACH ROW
BEGIN
    -- Prüfe, ob die LED-Position bereits verwendet wird
    IF EXISTS (SELECT 1 FROM led_mapping WHERE led_position = NEW.led_position) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'LED position already in use';
    END IF;
END//

CREATE TRIGGER before_update_led_mapping
BEFORE UPDATE ON led_mapping
FOR EACH ROW
BEGIN
    -- Prüfe, ob die neue LED-Position bereits verwendet wird (außer bei der aktuellen Zeile)
    IF EXISTS (SELECT 1 FROM led_mapping WHERE led_position = NEW.led_position AND part_id != NEW.part_id) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'LED position already in use';
    END IF;
END//

DELIMITER ; 