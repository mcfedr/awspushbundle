# Aws Push Bundle

A convenient bundle for registering devices and then pushing to them using amazons SNS service.

[![Latest Stable Version](https://poser.pugx.org/mcfedr/awspushbundle/v/stable.png)](https://packagist.org/packages/mcfedr/awspushbundle)
[![License](https://poser.pugx.org/mcfedr/awspushbundle/license.png)](https://packagist.org/packages/mcfedr/awspushbundle)
[![Build Status](https://travis-ci.org/mcfedr/awspushbundle.svg?branch=master)](https://travis-ci.org/mcfedr/awspushbundle)

## Install

### Composer

    php composer.phar require mcfedr/awspushbundle

### AppKernel

Include the bundle in your AppKernel

    public function registerBundles()
    {
        $bundles = array(
            ...
            new Mcfedr\AwsPushBundle\McfedrAwsPushBundle()

## Config

Put something like this in your config. The arns in the platforms section should be the preconfigured app arns in SNS.

    mcfedr_aws_push:
        platforms:
            ios: 'arn:aws:sns:....'
            android: 'arn:aws:sns:....'
        topic_arn: 'arn:aws:sns:...'
        aws:
            key: 'my key'
            secret: 'my secret'
            region: 'my region'

## Usage

Basically have a look at how the ApiController does its stuff

1. Register the device token

        $arn = $this->get('mcfedr_aws_push.devices')->registerDevice($token, $platform)

1. Send message to one device

        $this->get('mcfedr_aws_push.messages')->send($message, $arn)

1. Send message to all devices

        $this->get('mcfedr_aws_push.messages')->broadcast($message)


Alternative usage, using topics to send messages to lots of devices

1. Register the device token

        $arn = $this->get('mcfedr_aws_push.devices')->registerDevice($token, $platform)

1. Register the device on the topic

        $this->get('mcfedr_aws_push.topics')->registerDeviceOnTopic($arn, $topicArn)

1. Send messages

        $this->get('mcfedr_aws_push.topics')->broadcast($message, $topicArn)

If you later add a topic_name to the configuration you can run the `mcfedr:aws:subscribe` command to add your existing
devices to the topic.

## Commands

There are some commands to help manage the devices

1. `mcfedr:aws:enable`

    This will reenable all the devices

1. `mcfedr:aws:remove`

    This will remove any disabled devices. Its a good idea to do something like this regularly to remove old devices

1. `mcfedr:aws:subscribe`

    This will subscribe all devices to a topic, useful when introducing a topic

## Api Controller

There is a controller included which makes basic usage of the bundle very easy. You may or may not want to use it,
you might find it most useful as an example.

### Routing

If you want to use the default controller you need to setup the routing

    mcfedr_aws_push:
        resource: "@McfedrAwsPushBundle/Controller/"
        type:     annotation
        prefix:   /

### Usage

The controller provides two urls, both expect a JSON POST body

1. The first is a way to register a device

        POST /devices
        {
            "device": {
                "deviceId": "a push token",
                "platform": "the platform name in your config file"
            }
        }

1. The second is a way to send a broadcast message. If you are using a topic for all devices then don't send the platform
parameter.

        POST /broadcast
        {
            "broadcast": {
                "platform": "ios"
                "message": {
                    "text": "The plain text message to send",
                    "badge": 1
                }
            }
        }

## API documentation

You can see the full [API documentation](https://mcfedr.github.io/awspushbundle/) on github pages.
