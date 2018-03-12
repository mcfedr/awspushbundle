# Aws Push Bundle

A convenient bundle for registering devices and then pushing to them using amazons SNS service.

[![Latest Stable Version](https://poser.pugx.org/mcfedr/awspushbundle/v/stable.png)](https://packagist.org/packages/mcfedr/awspushbundle)
[![License](https://poser.pugx.org/mcfedr/awspushbundle/license.png)](https://packagist.org/packages/mcfedr/awspushbundle)
[![Build Status](https://travis-ci.org/mcfedr/awspushbundle.svg?branch=master)](https://travis-ci.org/mcfedr/awspushbundle)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/ab82b189-3854-43e5-9762-45bb620bcd0e/mini.png)](https://insight.sensiolabs.com/projects/ab82b189-3854-43e5-9762-45bb620bcd0e)

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
            credentials: 
                key: 'my key'
                secret: 'my secret'
            region: 'my region'

You can skip `credentials` if you have want the Aws SDK to get credentials indirectly, either from
[environment](https://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/credentials.html#environment-credentials) or
[ec2 role](https://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/credentials.html#using-iam-roles-for-amazon-ec2-instances).

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

## Text in notifications

For GCM and ADM there is no 'standard' key for text data as there is for Apple pushes, so this bundle send text in a key
called 'message'.

If localized text is sent the keys are
* `message-loc-key`
* `message-loc-args`

## 'Complicated' data on ADM

ADM only allows strings as values in the push data. This bundle lets you send 'complicated' values and will
automatically json encode these values for ADM. When it does this the key has `_json` added so that its easy to handle
this on the app side.

### Example

Sending:

    $message = new Message();
    $message->setCustom(['simple' => 'Hello', 'complicated' => ['inner' => 'value']]);
    
ADM received data:

    {"data": {"simple": "Hello", "complicated_json": "{\"inner\":\"value\"}"}}
    
To handle this data you should detect keys that end with `_json` and decode the values

**The applies to `message-loc-args` as well, they will always come as `message-loc-args_json` via ADM**

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

There are some extra dependencies you must add when using the controller

- sensio/framework-extra-bundle
- symfony/validator
- symfony/serializer
- symfony/property-info
- symfony/security-bundle
- symfony/expression-language

They also need enabling in the framework config

```yaml
framework:
    validation: { enable_annotations: true }
    serializer:
        enabled: true
    property_info:
        enabled: true
```

Add the routes in your `routing.yaml`:

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
