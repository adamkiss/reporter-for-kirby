<?php

namespace KirbyReporter\Client;

use KirbyReporter\Model\FormData;
use KirbyReporter\Report\ReportInterface;
use KirbyReporter\Report\ReportResponse;
use KirbyReporter\Traits\Expander;
use KirbyReporter\Traits\Request;
use KirbyReporter\Vendor\Vendor;

class BitbucketReport implements ReportInterface
{
    use Request;
    use Expander;

    private string $urlTemplate = "https://api.bitbucket.org/2.0/repositories/{user}/{repo}/issues";

    private Vendor $vendor;

    public function __construct(Vendor $vendor)
    {
        $this->vendor = $vendor;
    }

    public final function report(FormData $reportData, string $parsedTemplate): ReportResponse
    {
        $url = $this->expandUrl($this->urlTemplate, [
            "user" => $this->vendor->getOwner(),
            "repo" => $this->vendor->getRepository(),
        ]);

        $reportData = [
            "title" => $reportData->getTitle(),
            "description" => $parsedTemplate,
        ];

        $header = ["Authorization" => "Basic ".$this->getBasicAuth()];
        $request = $this->post($url, $reportData, $header);

        $status = $request->getStatusCode();
        $response = json_decode($request->getBody()->getContents(), true);
        $issueId = $response['id'];
        $issueUrl = $response['repository']['links']['html']['href'].'/issues/'.$issueId;

        return new ReportResponse($status, $issueUrl, $issueId);
    }

    private function getBasicAuth(): string
    {
        return base64_encode($this->vendor->getUser().':'.$this->vendor->getToken());
    }
}