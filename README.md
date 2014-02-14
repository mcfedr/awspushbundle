# AWS Push Bundle

A convenient bundle for registering devices and then pushing to them using amazons SNS service.

[![Latest Stable Version](https://poser.pugx.org/mcfedr/awspushbundle/v/stable.png)](https://packagist.org/packages/mcfedr/awspushbundle)
[![License](https://poser.pugx.org/mcfedr/awspushbundle/license.png)](https://packagist.org/packages/mcfedr/awspushbundle)

## Config

Put something like this in your config. The arns in the platforms section should be the preconfigured app arns in SNS.

    mcfedr_aws_push:
        platforms:
            ios: 'arn:aws:sns:....'
            android: 'arn:aws:sns:....'
        topic_name: 'my_topic'
        aws:
            key: 'my key'
            secret: 'my secret'
            region: 'my region'

## Usage

Basically have a look at how the APIController does its stuff

1. Register the device token

        $this->get('mcfedr_aws_push.devices')->registerDevice($token, $platform)

1. Send messages

        $this->get('mcfedr_aws_push.messages')->broadcast($message)


Alternative usage, using topics to send messages to lots of devices

1. Register the device token

        $arn = $this->get('mcfedr_aws_push.devices')->registerDevice($token, $platform)

1. Register the device on the topic

        $this->get('mcfedr_aws_push.topics')->registerDeviceOnTopic($arn, $topicName)

1. Send messages

        $this->get('mcfedr_aws_push.topics')->broadcast($message, $topicName)

If you later add a topic_name to the configuration you can run the `mcfedr:aws:subscribe` command to add your existing
devices to the topic.
