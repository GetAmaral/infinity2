#!/bin/bash
TIMESTAMP=$(date +%s)

curl -k -s -X POST "https://localhost/api/webhooks/whatsapp/cloud/test" \
  -H "Content-Type: application/json" \
  -d "{
    \"object\": \"whatsapp_business_account\",
    \"entry\": [{
      \"id\": \"test-entry-id\",
      \"changes\": [{
        \"value\": {
          \"messaging_product\": \"whatsapp\",
          \"metadata\": {
            \"display_phone_number\": \"5511999999999\",
            \"phone_number_id\": \"test-phone-id\"
          },
          \"messages\": [{
            \"from\": \"5511988776655\",
            \"id\": \"wamid.trace${TIMESTAMP}\",
            \"timestamp\": \"${TIMESTAMP}\",
            \"type\": \"text\",
            \"text\": {
              \"body\": \"Trace test ${TIMESTAMP}\"
            }
          }]
        },
        \"field\": \"messages\"
      }]
    }]
  }" | jq .
