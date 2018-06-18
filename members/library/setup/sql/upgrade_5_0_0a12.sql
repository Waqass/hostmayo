UPDATE customField SET fieldType=65 WHERE name='Password' AND groupId=2 AND subGroupId=1;

# Clean up ticket filters for users that do not exist
DELETE FROM `troubleticket_filters` WHERE user_id NOT IN (SELECT id FROM users) AND private = 1;