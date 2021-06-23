import json
import sys
import getopt

from AWSIoTPythonSDK.MQTTLib import AWSIoTMQTTClient


def send_request(msg):
    host = "a256izw0r9zkgi-ats.iot.us-east-1.amazonaws.com"
    root_ca_path = "/usr/local/softnas/files/aws/root-CA.crt"
    certificate_path = "/usr/local/softnas/files/aws/58f1f3c57f-certificate.pem.crt"
    private_key_path = "/usr/local/softnas/files/aws/58f1f3c57f-private.pem.key"
    port = 8883
    client_id = "SoftNAS"
    topic = "telemetry"
    with open('/var/www/softnas/version') as f:
        softnas_version = f.read().strip()
    useragent = "APN/1.0 SoftNAS/{ver} SoftNAS/{ver}".format(ver=softnas_version)
    version = '0.1.1'

    msg['useragent'] = useragent
    msg['version'] = version
    mqtt_client = AWSIoTMQTTClient(client_id)
    mqtt_client.configureEndpoint(host, port)
    mqtt_client.configureCredentials(root_ca_path, private_key_path, certificate_path)
    mqtt_client.configureAutoReconnectBackoffTime(1, 32, 20)
    mqtt_client.configureOfflinePublishQueueing(-1)
    mqtt_client.configureDrainingFrequency(2)
    mqtt_client.configureConnectDisconnectTimeout(10)
    mqtt_client.configureMQTTOperationTimeout(5)
    mqtt_client.connect()

    json_msg = json.dumps(msg)
    print('AWS report content', json_msg)
    if not mqtt_client.publish(topic, json_msg, 1):
        print('Failed to submit report')
        sys.exit(1)
    print('AWS report successful')


def submit_volume(instance_id, request_id, volume_id):
    message = {
        'type': 'volume',
        'attachment': instance_id,
        'requestid': request_id,
        'volumeid': volume_id
    }

    send_request(msg=message)


def submit_instance(instance_id, request_id):
    message = {
        'type': 'instance',
        'instanceid': instance_id,
        'requestid': request_id
    }
    send_request(msg=message)


def submit_snapshot(snapshot_id, request_id):
    message = {
        'type': 'snapshot',
        'snapshotid': snapshot_id,
        'request_id': request_id
    }
    send_request(msg=message)


def main(argv):
    instance_id = request_id = report_type = volume_id = snapshot_id = ''
    try:
        opts, args = getopt.getopt(argv, ':i:r:t:v:')
    except getopt.GetoptError:
        sys.exit(2)
    for opt, arg in opts:
        if opt == '-i':
            instance_id = arg
        elif opt == '-r':
            request_id = arg
        elif opt == '-t':
            report_type = arg
        elif opt == '-v':
            volume_id = arg
        elif opt == '-s':
            snapshot_id = arg

    if report_type == 'volume':
        submit_volume(instance_id=instance_id, request_id=request_id, volume_id=volume_id)
    elif report_type == 'instance':
        submit_instance(instance_id=instance_id, request_id=request_id)
    elif report_type == 'snapshot':
        submit_snapshot(snapshot_id=snapshot_id, request_id=request_id)


if __name__ == '__main__':
    main(sys.argv[1:])
