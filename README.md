# AWS Push Bundle

A convenient bundle for registering devices and then pushing to them using amazons SNS service.

## Config

Put something like this in your config. The arns in the platforms section should be the preconfigured app arns in SNS.

    mcfedr_aws_push:
        platforms:
            ios: 'arn:aws:sns:....'
            android: 'arn:aws:sns:....'
        aws:
            key: 'my key'
            secret: 'my secret'
            region: 'my region'

## Usage

Basically have a look at how the APIController does its stuff

1. Register the device token

        $this->get('mcfedr_aws_push.devices')->registerDevice($token, $platform)

1. Send messages

        $this->get('mcfedr_aws_push.devices')->messages->broadcast($message)
