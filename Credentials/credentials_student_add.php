<?php

/*
  Gibbon, Flexible & Open School System
  Copyright (C) 2010, Ross Parker

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Module\Credentials\CredentialsCredentialGateway;
use Gibbon\Module\Credentials\CredentialsWebsiteGateway;

if (isActionAccessible($guid, $connection2, '/modules/Credentials/credentials_student_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    $search = $_GET['search'] ?? '';
    $allStudents = $_GET['allStudents'] ?? '';

    if ($gibbonPersonID == '') {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $gibbonSchoolYearID = $_SESSION[$guid]['gibbonSchoolYearID'];

            $studentGateway = $container->get(CredentialsCredentialGateway::class);
            $searchColumns = $studentGateway->getSearchableColumns();

            $criteria = $studentGateway->newQueryCriteria()
            ->searchBy($searchColumns, $search)
            ->sortBy(['surname', 'preferredName'])
            ->filterBy('all', $allStudents ?? '')
            ->fromPOST();
            $students = $studentGateway->queryStudentBySchoolYear($criteria, $gibbonSchoolYearID, $gibbonPersonID);
        } catch (Exception $e) {
            echo "<div class='error'>" . $e->getMessage() . '</div>';
        }

        if ($students->getResultCount() != 1) {
            echo "<div class='error'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            $student = $students->getRow(0);

            //Proceed!
            $page->breadcrumbs->add(__('Manage Credentials'), 'credentials.php', [
                'search' => $search,
                'allStudents' => $allStudents,
            ]);
            $page->breadcrumbs->add(
                    Format::name('', $student['preferredName'], $student['surname'], 'Student'), 'credentials_student.php', [
                'gibbonPersonID' => $gibbonPersonID,
                'search' => $search,
                'allStudents' => $allStudents,
                    ]
            );
            $page->breadcrumbs->add(__('Add Credential'));

            $returns = array();
            $editLink = $_GET['editID'] ?? '';
            if ($editLink != '') {
                $editLink = $_SESSION[$guid]['absoluteURL'] . '/index.php?q=/modules/Credentials/credentials_student_edit.php&credentialsCredentialID=' . $_GET['editID'] . '&search=' . $_GET['search'] . '&allStudents' . $_GET['allStudents'] . '&gibbonPersonID=' . $_GET['gibbonPersonID'];
            }
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], $editLink, $returns);
            }

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='" . $_SESSION[$guid]['absoluteURL'] . "/index.php?q=/modules/Credentials/credentials_student.php&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents'>" . __('Back') . '</a>';
                echo '</div>';
            }

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'] . '/modules/' . $_SESSION[$guid]['module'] . '/credentials_student_addProcess.php?gibbonPersonID=' . $gibbonPersonID . '&search=' . $search . '&allStudents=' . $allStudents);

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $websiteGateway = $container->get(CredentialsWebsiteGateway::class);

            $criteria = $websiteGateway->newQueryCriteria()
                    ->sortBy(['title']);

            $websites = $websiteGateway->queryAllCredentialsWebsite($criteria);
            foreach ($websites as $key=>$web) {
                $arrweb[$key]['value'] = $web['credentialsWebsiteID'];
                $arrweb[$key]['title'] = $web['title'];
            }

            $sql = "SELECT credentialsWebsiteID as value, title as name FROM credentialsWebsite WHERE active='Y' ORDER BY title";
            $row = $form->addRow();
            $row->addLabel('credentialsWebsiteID', __('Website'));
            $row->addSelect('credentialsWebsiteID')->fromQuery($pdo, $sql)->required()->placeholder();

            $row = $form->addRow();
            $row->addLabel('username', __('Username'));
            $row->addTextField('username')->maxLength(50);

            $row = $form->addRow();
            $row->addLabel('password', __('Password'))->description(__('Saved using encryption, but displayed in plain text in the system.'));
            $row->addTextField('password')->maxLength(50);

            $row = $form->addRow();
            $row->addLabel('notes', __('Notes'));
            $row->addTextArea('notes')->setRows(5);

            $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
