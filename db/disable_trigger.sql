-- Disable the game availability trigger
-- This trigger is causing double updates when combined with manual PHP updates

-- Drop the trigger
DROP TRIGGER IF EXISTS trigger_update_game_availability ON borrow_transactions;

-- Drop the function
DROP FUNCTION IF EXISTS update_game_availability();

-- We're now handling game availability updates manually in PHP code
-- This provides better control and error handling

COMMENT ON TABLE borrow_transactions IS 'Game availability is managed manually in application code';
