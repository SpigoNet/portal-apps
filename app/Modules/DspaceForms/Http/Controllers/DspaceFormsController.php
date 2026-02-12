<?php

namespace App\Modules\DspaceForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DspaceForms\Models\DspaceEmailTemplate;
use App\Modules\DspaceForms\Models\DspaceForm;
use App\Modules\DspaceForms\Models\DspaceFormMap;
// NOVO: Para duplicação

// NOVO: Para duplicação

// NOVO: Para duplicação
use App\Modules\DspaceForms\Models\DspaceValuePairsList;
use App\Modules\DspaceForms\Models\DspaceXmlConfiguration;
// NOVO
use App\Modules\DspaceForms\Models\SubmissionProcess;
use App\Modules\DspaceForms\Models\SubmissionStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// NOVO
use Illuminate\Support\Facades\Storage;
// NOVO
use ZipArchive;

class DspaceFormsController extends Controller
{
    use DspaceConfigSession;

    /**
     * Tela principal (Dashboard do Módulo)
     */
    public function index(Request $request)
    {
        $configId = $this->getConfigId($request);
        $allConfigurations = DspaceXmlConfiguration::where('user_id', Auth::id())
            ->orderBy('name')
            ->get();

        if ($configId) {
            $config = DspaceXmlConfiguration::find($configId);

            if (! $config || $config->user_id !== Auth::id()) {
                $configId = null;
                $this->clearConfigId($request);
            }
        }

        if (! $configId && $allConfigurations->count() > 1) {
            return view('DspaceForms::selection', compact('allConfigurations'));
        }

        if (! $configId && $allConfigurations->isNotEmpty()) {
            $configId = $allConfigurations->first()->id;
            $this->setConfigId($request, $configId);
        }

        if ($configId) {
            $config = DspaceXmlConfiguration::findOrFail($configId);

            $stats = [
                'config_id' => $config->id,
                'config_name' => $config->name,
                'forms_count' => DspaceForm::where('xml_configuration_id', $configId)->count(),
                'vocabularies_count' => DspaceValuePairsList::where('xml_configuration_id', $configId)->count(),
                'maps_count' => DspaceFormMap::where('xml_configuration_id', $configId)->count(),
                'email_templates_count' => DspaceEmailTemplate::where('xml_configuration_id', $configId)->count(),
            ];

            return view('DspaceForms::index', compact('stats', 'allConfigurations', 'config'));
        }

        return view('DspaceForms::selection', compact('allConfigurations'));
    }

    public function selectConfig(Request $request, int $configId)
    {
        $config = DspaceXmlConfiguration::findOrFail($configId);

        if ($config->user_id !== Auth::id()) {
            abort(403, 'Acesso não autorizado.');
        }

        $this->setConfigId($request, $configId);

        return redirect()->route('dspace-forms.index');
    }

    public function clearConfig(Request $request)
    {
        $this->clearConfigId($request);

        return redirect()->route('dspace-forms.index');
    }

    private function seedDefaultEmailTemplates(int $configId): void
    {
        // Conteúdos das templates fornecidas pelo usuário
        $emailContents = [
            'batch_import_error' => "## Email sent to DSpace users when their batch import fails.\n##\n## Parameters: {0} the export error\n##             {1} the URL to the feedback page\n##\n##\n## See org.dspace.core.Email for information on the format of this file.\n##\n#set(\$subject = 'DSpace - The batch import was not completed.')\nThe batch import you initiated from the DSpace UI was not completed, due to the following reason:\n \${params[0]}\n\nFor more information you may contact your system administrator:\n \${params[1]}\n\n\n\nThe DSpace Team\n}",
            'batch_import_success' => "## Email sent to DSpace users when they successfully batch import items.\n##\n## Parameters: {0} the filepath to the mapfile created by the batch import\n##\n##\n## See org.dspace.core.Email for information on the format of this file.\n##\n#set(\$subject = 'DSpace - Batch import successfully completed')\nThe batch item import you initiated from the DSpace UI has completed successfully.\nYou may find the mapfile for the import in the following path: \${params[0]}\n\n\nThe DSpace Team\n}",
            'change_password' => "## Email sent to DSpace users when they forget their password.\n##\n## Parameters: {0} is expanded to a special URL\n##\n## See org.dspace.core.Email for information on the format of this file.\n##\n#set(\$subject = \"\${config.get('dspace.name')}: Change Password Request\")\n#set(\$phone = \${config.get('mail.message.helpdesk.telephone')})\nTo change the password for your \${config.get('dspace.name')} account, please click the link below:\n\n  \${params[0]}\n\nIf you need assistance with your account, please email \${config.get(\"mail.helpdesk\")}\n#if( \$phone )\nor call us at \${phone}.\n#end\n\nThe \${config.get('dspace.name')} Team\n}",
            'coar_notify_accepted' => "## Notification email sent when a request to review an item has been accepted by the service\n##\n## Parameters: {0} Service Name\n##             {1} Item Name\n##             {2} Service URL\n##             {3} Item URL\n##             {4} Submitter's Name\n##             {5} Date of the received LDN notification\n##\n##\n#set(\$subject \n= \"DSpace: The Service \${params[0]} has accepted to review the Item \"\"\${params[1]}\"\"\")\n\nAn acceptance notification has been received by the service: \${params[0]}\nfor the Item: \${params[1]}\n\nHere is a more detailed report:\nItem: \${params[1]}\nItem URL: \${params[3]}\nSubmitted by: \${params[4]}\n\nHas a new status: ONGOING REVIEW\n\nBy Service: \${params[0]}\nService URL: \${params[2]}\nDate: \${params[5]}\n\nThe \${config.get('dspace.name')} Team\n}",
            'coar_notify_endorsed' => "## Notification email sent when an item has been endorsed by a service\n##\n## Parameters: {0} Service Name\n##             {1} Item Name\n##             {2} Service URL\n##             {3} Item URL\n##             {4} Submitter's Name\n##             {5} Date of the received LDN notification\n##\n##\n#set(\$subject = \"DSpace: The Service \n\${params[0]} has endorsed the Item \"\"\${params[1]}\"\"\")\n\nAn endorsement announce notification has been received by the service: \${params[0]}\nfor the Item: \${params[1]}\n\nHere is a more detailed report:\nItem: \${params[1]}\nItem URL: \${params[3]}\nSubmitted by: \${params[4]}\n\nHas a new status: ENDORSED\n\nBy Service: \${params[0]}\nService URL: \${params[2]}\nDate: \${params[5]}\n\nThe \${config.get('dspace.name')} Team\n}",
            'coar_notify_rejected' => "## Notification email sent when a request to review an item has been rejected by the service\n##\n## Parameters: {0} Service Name\n##             {1} Item Name\n##             {2} Service URL\n##             {3} Item URL\n##             {4} Submitter's Name\n##             {5} Date of the received LDN notification\n##\n##\n#set(\$subject \n= \"DSpace: The Service \${params[0]} has refused to review the Item \"\"\${params[1]}\"\"\")\n\nA rejection notification has been received by the service: \${params[0]}\nfor the Item: \${params[1]}\n\nHere is a more detailed report:\nItem: \${params[1]}\nItem URL: \${params[3]}\nSubmitted by: \${params[4]}\n\nHas a new update: REJECTED REVIEW REQUEST\n\nBy Service: \${params[0]}\nService URL: \${params[2]}\nDate: \${params[5]}\n\nThe \${config.get('dspace.name')} Team\n}",
            'coar_notify_rejected_resubmission' => "## Notification email sent when a request to review an item has been tentatively rejected by the service\n## This means that a resubmission is needed following review.\n##\n## Parameters: {0} Service Name\n##             {1} Item Name\n##             {2} Service URL\n##             {3} Item URL\n##             {4} Submitter's Name\n##             {5} Date of the received LDN notification\n##\n##\n#set(\$subject = \"DSpace: The Service \${params[0]} indicates that revisions are needed following review of the Item \"\"\${params[1]}\"\"\")\n\nA \ntentative rejection notification has been received by the service: \${params[0]}\nfor the Item: \${params[1]}. Revisions are needed.\nTo further proceed, please submit a revised version when ready.\nHere is a more detailed report:\nItem: \${params[1]}\nItem URL: \${params[3]}\nSubmitted by: \${params[4]}\n\nHas a new update: REVISIONS ARE NEEDED FOLLOWING REVIEW\n\nBy Service: \${params[0]}\nService URL: \${params[2]}\nDate: \${params[5]}\n\nThe \${config.get('dspace.name')} Team\n}",
            'coar_notify_relationship' => "## Notification email sent that a resource has been related by a service\n##\n## Parameters: {0} Service Name\n##             {1} Item Name\n##             {2} Service URL\n##             {3} Item URL\n##             {4} Submitter's Name\n##             {5} Date of the received LDN notification\n##     \n        {6} LDN notification\n##             {7} Item\n##\n##\n#set(\$subject = \"DSpace: The Service \${params[0]} has related a Resource \"\"\${params[6].object.subject}\"\"\")\n\nA relationship announce notification has been received relating to the Item: \${params[1]}\n\nHere is a more detailed report:\nItem: \${params[1]}\nItem URL: \${params[3]}\nSubmitted by: \${params[4]}\nLinked Resource URL: \${params[6].object.subject}\n\nHas a new status: RELATED\n\nBy Service: \${params[0]}\nService URL: \${params[2]}\nDate: \${params[5]}\n\nThe \${config.get('dspace.name')} Team\n}",
            'coar_notify_reviewed' => "## Notification email sent when an item has been reviewed by a service\n##\n## Parameters: {0} Service Name\n##             {1} Item Name\n##             {2} Service URL\n##             {3} Item URL\n##             {4} Submitter's Name\n##             {5} Date of the received LDN notification\n##\n##\n#set(\$subject = \"DSpace: The Service \n\${params[0]} has reviewed the Item \"\"\${params[1]}\"\"\")\n\nA review announce notification has been received by the service: \${params[0]}\nfor the Item: \${params[1]}\n\nHere is a more detailed report:\nItem: \${params[1]}\nItem URL: \${params[3]}\nSubmitted by: \${params[4]}\n\nHas a new status: REVIEWED\n\nBy Service: \${params[0]}\nService URL: \${params[2]}\nDate: \${params[5]}\n\nThe \${config.get('dspace.name')} Team\n}",
            'doi_maintenance_error' => "## E-mail sent to designated address when a metadata update, registration\n## or reservation of a DOI fails.\n##\n## Parameters: {0} action (updating metadata of, registering or reserving)\n##             {1} Date & Time\n##             {2} resource type text\n##             {3} resource id\n##             {4} DOI\n##             {5} reason\n##\n## See org.dspace.core.Email for information on the format of this file.\n##\n#set(\$subject = \"\${config.get('dspace.name')}: Error \${params[0]} DOI \${params[3]}\")\n\nDate:    \${params[1]}\n\n\${params[0]} DOI \${params[4]} for \${params[2]} with ID \${params[3]} failed:\n\${params[5]}\n\nThe \${config.get('dspace.name')} Team\n}",
            'export_error' => "## Email sent to DSpace users when they successfully export an item or collection.\n##\n## Parameters: {0} the export error\n##             {1} the URL to the feedback page\n##\n##\n## See org.dspace.core.Email for information on the format of this file.\n##\n#set(\$subject = \"\${config.get('dspace.name')}: The item export you requested was not completed.\")\nThe item export you requested was not completed, due to the following reason:\n \${params[0]}\n\nFor more information you may contact your system administrator:\n \${params[1]}\n\nThe \${config.get('dspace.name')} Team\n}",
            'export_success' => "## Email sent to DSpace users when they successfully export an item or collection.\n##\n## Parameters: {0} is expanded to a special URL\n##             {1} configurable time (hours) an export file will be kept\n##\n## See org.dspace.core.Email for information on the format of this file.\n##\n#set(\$subject = \"\${config.get('dspace.name')}: Item export requested is ready for download\")\nThe item export you requested from the repository is now ready for download.\nYou may download the compressed file using the following link:\n\${params[0]}\n\nThis file will remain available for at least \${params[1]} hours.\nThe \${config.get('dspace.name')} Team\n}",
            'feedback' => "## E-mail sent with the information filled out in a feedback form.\n##\n## Parameters: {0} current date\n##             {1} email address that the user provided\n##             {2} logged in as\n##             {3} page that the user was on when they selected feedback\n##             {4} User-Agent HTTP Header\n##             {5} Session Id\n##       \n      {6} The user's comments\n##\n## See org.dspace.core.Email for information on the format of this file.\n##\n#set(\$subject = \"\${config.get('dspace.name')}: Feedback Form Information\")\n\nComments:\n\n\${params[6]}\n\n\nDate: \${params[0]}\nEmail: \${params[1]}\nLogged In As: \${params[2]}\nReferring Page: \${params[3]}\nUser Agent: \${params[4]}\nSession: \${params[5]}\n\nThe \${config.get('dspace.name')} Team\n}",
            'flowtask_notify' => "## Workflow curation task notification email message\n##\n## {0}  Title of submission\n## {1}  Name of collection\n## {2}  Submitter's name\n## {3}  Name of task operating\n## {4}  Task result\n## {5}  Workflow action taken\n##\n#set(\$subject = \"\${config.get('dspace.name')}: Curation Task Report\")\n\nTitle:         \${params[0]}\nCollection:    \${params[1]}\nSubmitted by:  \${params[2]}\n\nCuration task \${params[3]} has been performed\nwith the following result:\n\n\${params[4]}\n\nAction taken on the submission: \${params[5]}\n\nThe \${config.get('dspace.name')} Team\n}",
            'harvesting_error' => "## E-mail sent to designated address when a harvest process fails\n##\n## Parameters: {0} Collection id\n##             {1} Date & time\n##             {2} Status flag\n##             {3} Exception message\n##             {4} Exception stack trace\n##\n## See org.dspace.core.Email for information on the format of this file.\n##\n#set(\$subject = \"\${config.get('dspace.name')}: Harvesting Error\")\nCollection \${params[0]} failed on harvest:\n\nDate:       	\${params[1]}\nStatus Flag: 	\${params[2]}\n\n\${params[3]}\n\nException:\n\${params[4]}\n\nThe \${config.get('dspace.name')} Team\n}",
            'healthcheck' => "## E-mail sent by the healthcheck command to whomever is mentioned as -e parameter.\n##\n## Parameters: {0} is the output of healthcheck command\n## See org.dspace.core.Email for information on the format of this file.\n##\n#set(\$subject = \"\${config.get('dspace.name')}: Repository healthcheck\")\nThe healthcheck finished with the following output:\n\n\${params[0]}\n}",
            'internal_error' => "## E-mail sent to designated address when an internal server error occurs\n##\n## Parameters: {0} DSpace server URL\n##             {1} Date & time\n##             {2} Session ID\n##             {3} URL + HTTP parameters, if any\n##             {4} Exception stack trace\n##             {5} User details\n##   \n          {6} IP address\n##\n## See org.dspace.core.Email for information on the format of this file.\n##\n#set(\$subject = \"\${config.get('dspace.name')}: Internal Server Error\")\nAn internal server error occurred on \${params[0]}:\n\nDate:       \${params[1]}\nSession ID: \${params[2]}\nUser:       \${params[5]}\nIP address: \${params[6]}\n\n\${params[3]}\n\nException:\n\${params[4]}\n}",
            'orcid' => "## E-mail sent to DSpace users when they try to register with an ORCID account\n##\n## Parameters: {0} is expanded to a special registration URL\n##\n## See org.dspace.core.Email for information on the format of this file.\n##\n#set(\$subject = \"\${config.get('dspace.name')} Account Registration\")\n#set(\$phone = \${config.get('mail.message.helpdesk.telephone')})\nTo complete registration for a DSpace account, please click the link\nbelow:\n\n  \${params[0]}\n\nIf you need assistance with your account, please email\n\n  \${config.get(\"mail.helpdesk\")}\n#if( \$phone )\n\nor call us at \${phone}.\n#end\n\nThe \${config.get(\"dspace.name\")} Team\n}",
            'qaevent_admin_notification' => "## E-mail sent to notify Administrator of new Withdrawn/Reinstate request\n##\n## Parameters: {0} Type of request 'topic'\n##             {1} resource id\n##             {2} reason\n##\n#set(\$subject = \"Notification about \${params[0]} Request\")\n\nItem Details:\n\nType of request: \${params[0]}\nRelatem to item: \${params[1]}\nReason: \${params[2]}\n}",
            'register' => "## E-mail sent to DSpace users when they register for an account\n##\n## Parameters: {0} is expanded to a special registration URL\n##\n## See org.dspace.core.Email for information on the format of this file.\n##\n#set(\$subject = \"\${config.get('dspace.name')} Account Registration\")\n#set(\$phone = \${config.get('mail.message.helpdesk.telephone')})\nTo complete registration for a \${config.get('dspace.name')} account, please click the link below:\n\n  \${params[0]}\n\nIf you need assistance with your account, please email \${config.get(\"mail.helpdesk\")}\n#if( \$phone )\nor call us at \${phone}.\n#end\n\nThe \${config.get('dspace.name')} Team\n}",
            'registration_notify' => "## Registration notification email\n##\n## Parameters: {0} The name of the DSpace instance\n##             {1} The URL of the DSpace instance\n##             {2} Name:\n##             {3} Email:\n##             {4} Registration Date:\n##\n## See org.dspace.core.Email for information on the format of this file.\n##\n#set(\$subject = \"\${config.get('dspace.name')}: Registration Notification\")\n\nA new user has registered on \${params[0]} at \${params[1]}:\n\nName:                   \${params[2]}\nEmail:                  \${params[3]}\nDate:                   \${params[4]}\n\nThe \${config.get('dspace.name')} Team\n}",
            'request_item.admin' => "## E-mail sent when someone approving an item request, requests that the Item\n## or Bitstream be made public.\n##\n## Parameters: {0} the Handle of the requested bitstream, or \"all\"\n##             {1} the Handle of the item\n##             {2} the unique token which identifies the item request\n##             {3} the name of the approver\n##             {4} the approver's email address\n## See org.dspace.core.Email for information on the format of this file.\n##\n#set(\$subject = \"\${config.get('dspace.name')}: Request for Open Access\")\n\n\${params[3]}, with address \${params[4]},\nrequested the following document/file to be in Open Access:\n\nDocument Handle: \${params[1]}\nFile ID: \${params[0]}\nToken: \${params[2]}\n\nThe \${config.get('dspace.name')} Team\n}",
            'request_item.author' => "## E-mail sent to a restricted Item's author when a user requests a copy.\n##\n## Parameters: 0 requester's name\n##             1 requester's address\n##             2 name of a single bitstream, or \"all\"\n##             3 item Handle\n##             4 item title\n##             5 message from requester\n##             6 link \nback to DSpace for action\n##             7 corresponding author name\n##             8 corresponding author email\n##             9 configuration property \"dspace.name\"\n##             10 configuration property \"mail.helpdesk\"\n#set(\$subject = \"\${config.get('dspace.name')}: Request copy of document\")\n\nDear \${params[7]},\n\nA user of \${params[9]}, named \${params[0]} and using the email \${params[1]}, requested a copy of the file(s) associated with the document: \"\${params[4]}\" (\${params[3]}) submitted by \nyou.\n\nThis request came along with the following message:\n\n\"\${params[5]}\"\n\nTo answer, click \${params[6]}.\nWhether you choose to grant or deny the request, we think that it's in your best interest to respond.\nIF YOU ARE NOT AN AUTHOR OF THIS DOCUMENT, and only submitted the document on the author's behalf, PLEASE REDIRECT THIS MESSAGE TO THE AUTHOR(S).\nOnly the author(s) should answer the request to send a copy.\nIF YOU ARE AN AUTHOR OF THE REQUESTED DOCUMENT, thank you for your cooperation!\nIf you have any questions concerning this request, please contact \${params[10]}.\n\nThe \${config.get('dspace.name')} Team\n}",
            'request_item.granted' => "## Sent to the person requesting a copy of a restricted document when the\n## request is granted.\n##\n## Parameters:\n##  {0} name of the requester\n##  {1} Handle URL of the requested Item\n##  {2} title of the requested Item\n##  {3} name of the grantor\n##  {4} email address of the grantor (unused)\n##  {5} custom message sent by the grantor.\n#set(\$subject = 'Request for Copy of Restricted Document is Granted')\nDear \${params[0]}:\n\nYour request for a copy of the file(s) from the below document has been approved by \${params[3]}.\nYou may find the requested file(s) attached.\n\n    \${params[2]}\n    \${params[1]} \n#if( \${params[5]} )\n\nAn additional message from \${params[3]} follows:\n\n\${params[5]}\n#end\n\nBest regards,\nThe \${config.get('dspace.name')} Team\n}",
            'request_item.granted_token' => "## Sent to the person requesting a copy of a restricted document when the\n## request is granted.\n##\n## Parameters:\n##  {0} name of the requestor\n##  {1} Handle URL of the requested Item\n##  {2} title of the requested Item\n##  {3} name of the grantor\n##  {4} email address of the grantor (unused)\n##  {5} custom message sent by the grantor.\n##  {6} URL of the requested Item including the secure access token\n##  {7} expiration date of the granted access (timestamp, formatted in requestitem.cfg)\n\n#set(\$subject = 'Request for Copy of Restricted Document is Granted')\nDear \${params[0]}:\n\nYour request for a copy of the file(s) from the below document has been approved.\nThe following link will grant access to the files you requested.\nSecure link: \${params[6]}\n\n    \${params[2]}\n    \${params[1]}\n\n#if( \${params[7]} )\n\n\nYour access will last until \${params[7]}.\n#end\n#if( \${params[5]} )\n\n\n\nAn additional message from the approver follows:\n\n\${params[5]}\n#end\n\nBest regards,\nThe \${config.get('dspace.name')} Team\n}",
            'request_item.rejected' => "## Sent to the person requesting a copy of a restricted document when the\n## request is denied.\n##\n## Parameters:\n##  {0} name of the requester\n##  {1} Handle URL of the requested Item\n##  {2} title of the requested Item\n##  {3} name of the grantor\n##  {4} email address of the grantor (unused)\n##  {5} custom message sent by the grantor.\n#set(\$subject = 'Request for Copy of Restricted Document is Denied')\nDear \${params[0]}:\n\nYour request for a copy of the file(s) from the below document has been denied by \${params[3]}.\n\${params[2]}\n    \${params[1]} \n#if( \${params[5]} )\n\nAn additional message from \${params[3]} follows:\n\n\${params[5]}\n#end\n\nBest regards,\nThe \${config.get('dspace.name')} Team\n}",
            'submit_archive' => "## Item Archived email message\n##\n## {0}  Title of submission\n## {1}  Name of collection\n## {2}  handle\n##\n#set(\$subject = \"\${config.get('dspace.name')}: Submission Approved and Archived\")\n\nYou submitted: \${params[0]}\n\nTo collection: \${params[1]}\n\nYour submission has been accepted and archived in \${config.get('dspace.name')},\nand it has been assigned the following identifier:\n\${params[2]}\n\nPlease use this identifier when citing your submission.\nMany thanks!\n\nThe \${config.get('dspace.name')} Team\n}",
            'submit_reject' => "## Rejection email message\n##\n## {0}  Title of submission\n## {1}  Name of collection\n## {2}  Name of the rejector\n## {3}  Reason for the rejection\n## {4}  Link to 'My DSpace' page\n##\n#set(\$subject = \"\${config.get('dspace.name')}: Submission Rejected\")\n\nYou submitted: \${params[0]}\n\nTo collection: \${params[1]}\n\nYour submission has been rejected by \${params[2]}\nwith the following explanation:\n\n\${params[3]}\n\nYour submission has not been deleted.\nYou can access it from your \"My\${config.get('dspace.shortname')}\" page: \${params[4]}\n\nThe \${config.get('dspace.name')} Team\n}",
            'submit_task' => "## Workflow task email message\n##\n## {0}  Title of submission\n## {1}  Name of collection\n## {2}  submitter's name\n## {3}  Description of task\n## {4}  link to 'my DSpace' page\n##\n#set(\$subject = \"\${config.get('dspace.name')}: You have a new task\")\n\nA new item has been submitted:\n\nTitle:        \${params[0]}\nCollection:   \${params[1]}\nSubmitted by: \${params[2]}\n\n\${params[3]}\n\nTo claim this task, please visit your \"My\${config.get('dspace.shortname')}\"\npage:  \${params[4]}\n\nMany thanks!\nThe \${config.get('dspace.name')} Team\n}",
            'subscriptions_content' => "## E-mail sent to designated address about updates on subscribed items\n##\n## Parameters: {0} Communities updates\n##             {1} Collections updates\n#set(\$subject = \"\${config.get('dspace.name')} Subscriptions\")\nThis email is sent from \${config.get('dspace.name')} based on the chosen subscription preferences.\n#if( not( \"\${params[0]}\" == \"\" ))\nCommunity Subscriptions:\n------------------------\nList of changed items : \${params[0]}\n\n#end\n#if( not( \"\${params[1]}\" == \"\" ))\nCollection Subscriptions:\n-------------------------\nList of changed items : \${params[1]}\n#end\n}",
            'validation_orcid' => "## E-mail sent to DSpace users when they confirm the orcid email address for the account\n##\n## Parameters: {0} is expanded to a special registration URL\n##\n## See org.dspace.core.Email for information on the format of this file.\n##\n#set(\$subject = \"\${config.get('dspace.name')} Account Registration\")\n#set(\$phone = \${config.get('mail.message.helpdesk.telephone')})\nTo confirm your email and create the needed account, please click the link\nbelow:\n\n  \${params[0]}\n\nIf you need assistance with your account, please email\n\n  \${config.get(\"mail.helpdesk\")}\n#if( \$phone )\n\nor call us at \${phone}.\n#end\n\nThe \${config.get(\"dspace.name\")} Team\n}",
            'welcome' => "## E-mail sent to a DSpace user after a new account is registered.\n##\n## See org.dspace.core.Email for information on the format of this file.\n##\n#set(\$subject = \"Welcome new registered \${config.get('dspace.name')} user!\")\nThank you for registering an account.\nYour new account can be used immediately\nto subscribe to notices of new content arriving in collections of your choice.\nYour new account can also be granted privileges to submit new content, or to\nedit and/or approve submissions.\nIf you need assistance with your account, please email \${config.get(\"mail.helpdesk\")}.\n\nThe \${config.get('dspace.name')} Team\n}",
        ];

        foreach ($emailContents as $name => $content) {
            // Tentativa de extrair o assunto usando regex (padrão Velocity)
            $subject = null;
            if (preg_match('/#set\(\$subject = ["\']?([^"\']+)["\']?\)/', $content, $matches)) {
                $subject = trim($matches[1]);
                // Remove a sintaxe Velocity interna para exibição na UI
                $subject = preg_replace('/\${config\.get\([^\)]+\)}/', '[Nome DSpace]', $subject);
                $subject = preg_replace('/\${params\[\d+\]}/', '[Parâmetro]', $subject);
            }

            // Tentativa de extrair a primeira linha de comentário como descrição
            $description = null;
            if (preg_match('/## ([^\n]+)/', $content, $descMatches)) {
                $description = trim($descMatches[1]);
            }

            DspaceEmailTemplate::create([
                'xml_configuration_id' => $configId,
                'name' => $name,
                'subject' => $subject,
                'content' => $content,
                'description' => $description,
            ]);
        }
    }

    /**
     * Exibe o formulário para criar uma nova configuração.
     */
    public function create()
    {
        return view('DspaceForms::configurations.create');
    }

    /**
     * Salva uma nova configuração.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:dspace_xml_configurations,name,NULL,id,user_id,'.Auth::id(),
            'description' => 'nullable|string',
        ], [
            'name.unique' => 'Você já possui uma configuração com este nome.',
        ]);

        $config = DspaceXmlConfiguration::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'description' => $request->description,
        ]);

        $this->setConfigId($request, $config->id);

        return redirect()->route('dspace-forms.index')
            ->with('success', 'Configuração criada com sucesso.');
    }

    // --- MÉTODOS DE EXPORTAÇÃO (FILTRADOS) ---

    /**
     * Duplica uma configuração existente e todos os seus dados dependentes.
     */
    public function duplicate(Request $request, DspaceXmlConfiguration $configuration)
    {
        if ($configuration->user_id !== Auth::id()) {
            return back()->with('error', 'Acesso não autorizado para duplicar esta configuração.');
        }

        // 1. Cria a nova configuração (Clone)
        $newConfig = $configuration->replicate();
        $newConfig->name = $configuration->name.' (Cópia '.now()->format('YmdHis').')';
        $newConfig->created_at = now();
        $newConfig->updated_at = now();
        $newConfig->save();

        $newId = $newConfig->id;

        // 2. Duplicação em Cascata

        // Duplicar Mapeamentos (dspace_form_maps)
        $configuration->formMaps()->each(function ($map) use ($newId) {
            $newMap = $map->replicate();
            $newMap->xml_configuration_id = $newId;
            $newMap->save();
        });

        // Duplicar Listas de Valores (dspace_value_pairs_lists) e seus Pares (dspace_value_pairs)
        $configuration->valuePairsLists()->with('pairs')->each(function ($list) use ($newId) {
            $newList = $list->replicate();
            $newList->xml_configuration_id = $newId;
            $newList->save();

            // Duplicar os pares de valor internos
            foreach ($list->pairs as $pair) {
                $newPair = $pair->replicate();
                $newPair->list_id = $newList->id; // Vincula ao novo ID da lista
                $newPair->save();
            }
        });

        // Duplicar Formulários (dspace_forms) e todas as suas dependências
        $configuration->forms()->with('rows.fields', 'rows.relationFields')->each(function ($form) use ($newId) {
            $newForm = $form->replicate();
            $newForm->xml_configuration_id = $newId;
            $newForm->save();
            $newFormId = $newForm->id;

            // Duplicar Rows
            foreach ($form->rows as $row) {
                $newRow = $row->replicate();
                $newRow->form_id = $newFormId;
                $newRow->save();
                $newRowId = $newRow->id;

                // Duplicar Fields
                foreach ($row->fields as $field) {
                    $newField = $field->replicate();
                    $newField->row_id = $newRowId;
                    $newField->save();
                }

                // Duplicar Relation Fields
                foreach ($row->relationFields as $rField) {
                    $newRField = $rField->replicate();
                    $newRField->row_id = $newRowId;
                    $newRField->save();
                }
            }
        });

        // Duplicar Processos de Submissão (dspace_submission_processes) e seus Passos
        $configuration->submissionProcesses()->with('steps')->each(function ($process) use ($newId) {
            $newProcess = $process->replicate();
            $newProcess->xml_configuration_id = $newId;
            $newProcess->save();
            $newProcessId = $newProcess->id;

            // Duplicar os passos
            foreach ($process->steps as $step) {
                $newStep = $step->replicate();
                $newStep->submission_process_id = $newProcessId;
                $newStep->save();
            }
        });

        // NOVO: Duplicar Templates de E-mail (dspace_email_templates)
        $configuration->emailTemplates()->each(function ($template) use ($newId) {
            $newTemplate = $template->replicate();
            $newTemplate->xml_configuration_id = $newId;
            $newTemplate->save();
        });

        $this->setConfigId($request, $newId);

        return redirect()->route('dspace-forms.index')
            ->with('success', "Configuração '{$configuration->name}' duplicada para '{$newConfig->name}'.");
    }

    /**
     * Gera e exporta um arquivo ZIP com todas as configurações.
     */
    public function exportAllAsZip(Request $request)
    {
        $config = $this->requireConfig($request);
        if ($config instanceof \Illuminate\Http\RedirectResponse) {
            return $config;
        }

        $configId = $config->id;

        $zip = new ZipArchive;
        $zipFileName = 'dspace_config_'.$config->name.'_'.date('Y-m-d_His').'.zip';
        $zipPath = storage_path('app/'.$zipFileName);

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return back()->with('error', 'Não foi possível criar o arquivo ZIP.');
        }

        // 1. Gerar e adicionar submission-forms.xml
        $submissionFormsContent = $this->generateSubmissionFormsXmlContent($configId);
        $zip->addFromString('submission-forms.xml', $submissionFormsContent);

        // 2. Gerar e adicionar item-submission.xml
        $itemSubmissionContent = $this->generateItemSubmissionXmlContent($configId);
        $zip->addFromString('item-submission.xml', $itemSubmissionContent);
        // Adiciona um DTD placeholder para item-submission
        if (Storage::disk('local')->exists('dspace_templates/item-submission-dtd-placeholder.dtd')) {
            $zip->addFile(storage_path('app/dspace_templates/item-submission-dtd-placeholder.dtd'), 'item-submission.dtd');
        }

        // 3. Adicionar vocabulários e seus XSDs
        $zip->addEmptyDir('controlled-vocabularies');

        // Obtém os nomes dos vocabulários que estão em uso NA CONFIGURAÇÃO ATUAL
        $usedVocabularies = DspaceForm::where('xml_configuration_id', $configId)
            ->join('dspace_form_rows', 'dspace_forms.id', '=', 'dspace_form_rows.form_id')
            ->join('dspace_form_fields', 'dspace_form_rows.id', '=', 'dspace_form_fields.row_id')
            ->whereNotNull('dspace_form_fields.vocabulary')
            ->distinct()
            ->pluck('dspace_form_fields.vocabulary')
            ->toArray();

        // Filtro para exportar apenas os vocabulários usados E não 'riccps' E DA CONFIGURAÇÃO ATUAL
        $vocabularies = DspaceValuePairsList::with('pairs')
            ->where('xml_configuration_id', $configId)
            ->whereIn('name', $usedVocabularies)
            ->where('name', '!=', 'riccps')
            ->get();

        // Carrega o conteúdo XSD do modelo uma única vez
        $xsdContent = Storage::disk('local')->exists('dspace_templates/vocabulary.xsd')
            ? Storage::disk('local')->get('dspace_templates/vocabulary.xsd')
            : '';

        foreach ($vocabularies as $vocabulary) {
            $vocabularyXmlContent = $this->generateVocabularyXmlContent($vocabulary);
            $zip->addFromString('controlled-vocabularies/'.$vocabulary->name.'.xml', $vocabularyXmlContent);

            // Adiciona um arquivo XSD separado para cada vocabulário
            if ($xsdContent) {
                $zip->addFromString('controlled-vocabularies/'.$vocabulary->name.'.xsd', $xsdContent);
            }
        }
        $zip->addEmptyDir('emails');
        $emailTemplates = DspaceEmailTemplate::where('xml_configuration_id', $configId)
            ->get();

        foreach ($emailTemplates as $template) {
            // Usa o nome da template (e.g., 'register', 'request_item.author') como nome do arquivo.
            $zip->addFromString('emails/'.$template->name, $template->content);
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    private function generateSubmissionFormsXmlContent($configId): string
    {
        $forms = DspaceForm::where('xml_configuration_id', $configId)->with('rows.fields', 'rows.relationFields')->get();

        // 1. Descobrir quais listas de valores estão REALMENTE em uso NA CONFIGURAÇÃO.
        $usedListNames = DspaceForm::where('xml_configuration_id', $configId)
            ->join('dspace_form_rows', 'dspace_forms.id', '=', 'dspace_form_rows.form_id')
            ->join('dspace_form_fields', 'dspace_form_rows.id', '=', 'dspace_form_fields.row_id')
            ->whereNotNull('dspace_form_fields.value_pairs_name')
            ->where('dspace_form_fields.value_pairs_name', '!=', '')
            ->distinct()
            ->pluck('dspace_form_fields.value_pairs_name')
            ->all();

        // 2. Buscar APENAS as listas que estão em uso E pertencem a esta configuração.
        $valueLists = DspaceValuePairsList::with('pairs')
            ->where('xml_configuration_id', $configId)
            ->whereIn('name', $usedListNames)
            ->get();

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><input-forms/>');
        $formDefs = $xml->addChild('form-definitions');

        // FUNÇÃO AUXILIAR PARA LIMPEZA DE TEXTO (Remoção de quebras de linha e múltiplos espaços)
        $cleanXmlText = function ($text) {
            if (is_null($text)) {
                return '';
            }
            // 1. Substitui quebras de linha/retornos de carro e tabs por espaço
            $text = str_replace(["\r", "\n", "\t"], ' ', $text);
            // 2. Remove múltiplos espaços em branco, deixando apenas um
            $text = preg_replace('/\s+/', ' ', $text);

            // 3. Remove espaços no início e fim
            return trim($text);
        };

        foreach ($forms as $form) {
            $formNode = $formDefs->addChild('form');
            $formNode->addAttribute('name', $form->name);
            foreach ($form->rows as $row) {

                // VERIFICAÇÃO 1: Se a row não tiver fields, PULAR a row e seus relationFields.
                if ($row->fields->isEmpty() && $row->relationFields->isEmpty()) {
                    continue; // Pula para a próxima row
                }

                $rowNode = $formNode->addChild('row');
                foreach ($row->fields as $field) {
                    $fieldNode = $rowNode->addChild('field');
                    // Adiciona os elementos do field...
                    $fieldNode->addChild('dc-schema', $cleanXmlText($field->dc_schema));
                    $fieldNode->addChild('dc-element', $cleanXmlText($field->dc_element));
                    if ($field->dc_qualifier) {
                        $fieldNode->addChild('dc-qualifier', $cleanXmlText($field->dc_qualifier));
                    }
                    $fieldNode->addChild('repeatable', $field->repeatable ? 'true' : 'false');
                    // APLICAR LIMPEZA AQUI
                    $fieldNode->addChild('label', $cleanXmlText($field->label));
                    $inputType = $fieldNode->addChild('input-type', $cleanXmlText($field->input_type));
                    if ($field->value_pairs_name) {
                        $inputType->addAttribute('value-pairs-name', $cleanXmlText($field->value_pairs_name));
                    }
                    // APLICAR LIMPEZA AQUI
                    $fieldNode->addChild('hint', $cleanXmlText($field->hint));

                    // APLICAR LIMPEZA AQUI
                    if ($field->required) {
                        $fieldNode->addChild('required', $cleanXmlText($field->required) ?? '');
                    }
                    if ($field->vocabulary) {
                        $vocabNode = $fieldNode->addChild('vocabulary', $cleanXmlText($field->vocabulary));
                        if ($field->vocabulary_closed) {
                            $vocabNode->addAttribute('closed', 'true');
                        }
                    }
                }
                // Adicionar lógica para relationFields se necessário...
                // (Os relationFields também devem ser considerados para evitar <row></row> vazias se só houverem eles)
                foreach ($row->relationFields as $rField) {
                    // Lógica de exportação dos relationFields (apenas para garantir que a tag <row> é gerada)
                    // ... Seu código para relationFields aqui, aplicando $cleanXmlText onde necessário ...
                }
            }
        }

        $valuePairs = $xml->addChild('form-value-pairs');
        foreach ($valueLists as $list) {
            $listNode = $valuePairs->addChild('value-pairs');
            $listNode->addAttribute('value-pairs-name', $cleanXmlText($list->name));
            $listNode->addAttribute('dc-term', $cleanXmlText($list->dc_term));
            foreach ($list->pairs()->orderBy('order')->get() as $pair) {
                $pairNode = $listNode->addChild('pair');
                // APLICAR LIMPEZA AQUI e ENCODING DE CARACTERES
                $displayedValue = htmlspecialchars($cleanXmlText($pair->displayed_value));
                $storedValue = htmlspecialchars($cleanXmlText($pair->stored_value ?? ''), ENT_QUOTES, 'UTF-8');

                $pairNode->addChild('displayed-value', $displayedValue);
                $pairNode->addChild('stored-value', $storedValue !== '' ? $storedValue : '');
            }
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        // Expandir tags vazias
        $this->expandEmptyTags($dom->documentElement);

        $implementation = new \DOMImplementation;
        $dtd = $implementation->createDocumentType('input-forms', '', 'submission-forms.dtd');
        $dom->insertBefore($dtd, $dom->documentElement);

        return $dom->saveXML();
    }

    // Os métodos generateVocabularyXmlContent e expandEmptyTags permanecem inalterados

    private function expandEmptyTags(\DOMElement $element): void
    {
        if ($element->childNodes->length === 0) {
            $element->appendChild($element->ownerDocument->createTextNode(''));
        } else {
            foreach ($element->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $this->expandEmptyTags($child);
                }
            }
        }
    }

    private function generateItemSubmissionXmlContent($configId): string
    {
        $maps = DspaceFormMap::where('xml_configuration_id', $configId)->get();
        // Assumindo que SubmissionProcess tem o escopo 'xml_configuration_id'
        $processes = SubmissionProcess::where('xml_configuration_id', $configId)->with('steps')->get();

        $stepIds = SubmissionStep::whereHas('process', function ($query) use ($configId) {
            // Garante que os steps estão vinculados a processos desta configuração
            $query->where('xml_configuration_id', $configId);
        })->distinct()->pluck('step_id');

        $xml = new \SimpleXMLElement('<item-submission />');

        $submissionMap = $xml->addChild('submission-map');
        foreach ($maps as $map) {
            $mapNode = $submissionMap->addChild('name-map');
            if ($map->map_type === 'handle') {
                $mapNode->addAttribute('collection-handle', $map->map_key);
            } elseif ($map->map_type === 'entity-type') {
                $mapNode->addAttribute('collection-entity-type', $map->map_key);
            }
            $mapNode->addAttribute('submission-name', $map->submission_name);
        }

        $stepDefs = $xml->addChild('step-definitions');
        foreach ($stepIds as $stepId) {
            $stepDefNode = $stepDefs->addChild('step-definition');
            $stepDefNode->addAttribute('id', $stepId);

            // Adiciona conteúdo baseado no ID do step, conforme os exemplos
            switch ($stepId) {
                case 'collection':
                    $stepDefNode->addChild('heading');
                    $stepDefNode->addChild('processing-class', 'org.dspace.app.rest.submit.step.CollectionStep');
                    $stepDefNode->addChild('type', 'collection');
                    break;

                case 'upload':
                    $stepDefNode->addChild('heading', 'submit.progressbar.upload');
                    $stepDefNode->addChild('processing-class', 'org.dspace.app.rest.submit.step.UploadStep');
                    $stepDefNode->addChild('type', 'upload');
                    break;

                case 'license':
                    $stepDefNode->addChild('heading', 'submit.progressbar.license');
                    $stepDefNode->addChild('processing-class', 'org.dspace.app.rest.submit.step.LicenseStep');
                    $stepDefNode->addChild('type', 'license');
                    $scopeNode = $stepDefNode->addChild('scope', 'submission');
                    $scopeNode->addAttribute('visibilityOutside', 'read-only');
                    break;

                    // Caso padrão para formulários dinâmicos (ex: artigopageone, tccpagetwo, etc.)
                default:
                    $stepDefNode->addAttribute('mandatory', 'true');

                    // Lógica para heading (stepone vs steptwo)
                    if (str_ends_with(strtolower($stepId), 'two') || str_ends_with(strtolower($stepId), '2')) {
                        $stepDefNode->addChild('heading', 'submit.progressbar.describe.steptwo');
                    } else {
                        // Fallback para todos os outros (pageone, personStep, etc.)
                        $stepDefNode->addChild('heading', 'submit.progressbar.describe.stepone');
                    }

                    $stepDefNode->addChild('processing-class', 'org.dspace.app.rest.submit.step.DescribeStep');
                    $stepDefNode->addChild('type', 'submission-form');
                    break;
            }
        }

        $submissionDefs = $xml->addChild('submission-definitions');
        foreach ($processes as $process) {
            $processNode = $submissionDefs->addChild('submission-process');
            $processNode->addAttribute('name', $process->name);
            foreach ($process->steps as $step) {
                $stepNode = $processNode->addChild('step');
                $stepNode->addAttribute('id', $step->step_id);
            }
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        // Expandir tags vazias
        $this->expandEmptyTags($dom->documentElement);

        $implementation = new \DOMImplementation;
        $dtd = $implementation->createDocumentType('item-submission', '', 'item-submission.dtd');
        $dom->insertBefore($dtd, $dom->documentElement);

        return $dom->saveXML();
    }

    private function generateVocabularyXmlContent(DspaceValuePairsList $list): string
    {
        // CORREÇÃO AQUI: Adicionar declaração UTF-8
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><node />');

        $xml->addAttribute('id', $list->name);
        // label removido htmlspecialchars se houver
        $xml->addAttribute('label', '');
        $isComposedBy = $xml->addChild('isComposedBy');

        foreach ($list->pairs()->orderBy('order')->get() as $pair) {
            $node = $isComposedBy->addChild('node');
            $node->addAttribute('id', $pair->id);
            // Mantenha sem htmlspecialchars aqui também
            $node->addAttribute('label', $pair->displayed_value);
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        // O asXML() agora retornará UTF-8 correto graças ao cabeçalho adicionado acima
        $dom->loadXML($xml->asXML());

        $this->expandEmptyTags($dom->documentElement);

        return $dom->saveXML();
    }
}
