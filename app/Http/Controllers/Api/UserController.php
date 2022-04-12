<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;

class UserController extends Controller
{
    //
    public function index()
    {
        $users = UserModel::all('name', 'email');
        return response()->json($users);
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        return UserModel::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }

    public function show($value)
    {
        $users = UserModel::select('name', 'email') -> where('name', $value) -> orwhere('email', $value) -> paginate(10);

        return response()->json($users);
    }

    public function filter($name, $email)
    {
        if(!empty($name) && !empty($email))
        {
            $users = UserModel::select('name', 'email')-> where('name', $name) ->where('email', $email) -> paginate(10);
        }     
            
        return response()->json($users);
    }

    public function update($id, Request $request)
    {
        $users = UserModel::findOrFail($id);
        $this->validate($request, [
            'name' => 'required',
            'password' => 'required'
        ]);

        $input = $request->all();
        $users->fill($input)->save();

        return response()->json([
            'message' => "record successfully updated",
            $users->id, $users->name, $users->email]);
    }

    public function destroy($id)
    {
        $users = UserModel::findOrFail($id);
        $users->delete();
        return response()->json([
            'message' => "record successfully deleted",
            $users->id, $users->name, $users->email]);
    }

    public function uploadContent(Request $request)
    {
        $file = $request->file('uploaded_file');
        if ($file) {
            $filename = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension(); //Get extension of uploaded file
            $tempPath = $file->getRealPath();
            $fileSize = $file->getSize(); //Get size of uploaded file in bytes
            
            //Check for file extension and size
            $this->checkUploadedFileProperties($extension, $fileSize);
            
            //Where uploaded file will be stored on the server 
            $location = 'uploads'; //Created an "uploads" folder for that
            
            // Upload file
            $file->move($location, $filename);
            
            // In case the uploaded file path is to be stored in the database 
            $filepath = public_path($location . "/" . $filename);
            
            // Reading file
            $file = fopen($filepath, "r");
            $importData_arr = array(); // Read through the file and store the contents as an array
            $i = 0;
            
            //Read the contents of the uploaded file 
            while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
                $num = count($filedata);
                // Skip first row (Remove below comment if you want to skip the first row)
                if ($i == 0) {
                    $i++;
                    continue;
                }
                for ($c = 0; $c < $num; $c++) {
                    $importData_arr[$i][] = $filedata[$c];
                }
                $i++;
            }
            
            fclose($file); //Close after reading
            $j = 0;
            foreach ($importData_arr as $importData) {
                $name = $importData[1]; //Get user names
                $email = $importData[2]; //Get the user emails
                $j++;

                try {
                    DB::beginTransaction();
                    UserModel::create([
                        'name' => $importData[1],
                        'email' => $importData[2],
                        'password' => $importData[3],
                    ]);

                    DB::commit();
                } catch (\Exception $e) {
                //throw $th;
                DB::rollBack();
                }
            }
            return response()->json([
            'message' => "$j records successfully uploaded"
            ]);
        } 

        else {
            //no file was uploaded
            throw new \Exception('No file was uploaded', Response::HTTP_BAD_REQUEST);
        }
    }
    public function checkUploadedFileProperties($extension, $fileSize)
    {
        $valid_extension = array("csv", "xlsx"); //Only want csv and excel files
        $maxFileSize = 2097152; // Uploaded file size limit is 2mb
        
        if (in_array(strtolower($extension), $valid_extension)) {
            if ($fileSize <= $maxFileSize) {} 
            else {
                throw new \Exception('No file was uploaded', Response::HTTP_REQUEST_ENTITY_TOO_LARGE); //413 error
            }
        } 
        else {
            throw new \Exception('Invalid file extension', Response::HTTP_UNSUPPORTED_MEDIA_TYPE); //415 error
        }
    }
    
}
