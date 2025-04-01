<?php

namespace App\Http\Controllers;

use App\Events\BookBorrowed;
use App\Http\Resources\BookHistoryResource;
use App\Http\Resources\BookResource;
use App\Http\Resources\BookWiseHistoryResource;
use App\Models\Book;
use App\Models\BorrowHistory;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class BookController extends ApiController
{
    /**
     * @OA\Get(
     *     path="/api/books",
     *     summary="Get list of books",
     *     description="Get a paginated list of books with search functionality. The list includes book details along with borrower information.",
     *     tags={"Books"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", default=1),
     *         description="Page number for pagination"
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", default=10),
     *         description="Number of records per page"
     *     ),
     *     @OA\Parameter(
     *         name="search_term",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         description="Search term to filter books by name or details"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Books retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Books retrieved successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="books", type="array", 
     *                     @OA\Items(ref="#/components/schemas/BookResource")
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=100)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $page = $request->page ? $request->page : 1;
            $limit = $request->limit ? $request->limit : 10;
            $searchTerm = $request->search_term ?? '';

            $cacheKey = "books_{$page}_{$limit}_" . md5($searchTerm);

            $books = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($searchTerm, $page, $limit) {
                $query = Book::with('borrower_details');

                if (!empty($searchTerm)) {
                    $query->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('details', 'like', "%{$searchTerm}%");
                }

                $total = $query->count();
                $offset = ($page - 1) * $limit;

                $books = $query->offset($offset)->limit($limit)->get();

                return [
                    'books' => $books,
                    'total' => $total,
                ];
            });

            $this->status = $this->statusCode["success"];
            $this->response["message"] = "Books retrieved successfully.";
            $this->response["data"]["books"] = BookResource::collection($books['books']);
            $this->response["data"]["total"] = $books['total'];
        } catch (Exception $exception) {
            $this->status = $this->statusCode["internal_server_error"];
            $this->response["message"] = "Something went wrong.";
            $this->response["error"] = $exception->getMessage();
        }
        return response()->json($this->response, $this->status);
    }

    /**
     * @OA\Post(
     *     path="/api/books",
     *     summary="Create a new book",
     *     description="Adds a new book to the system with the provided name, details, and automatically generates a slug.",
     *     tags={"Books"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Book data to be created",
     *         @OA\JsonContent(
     *             required={"name", "details"},
     *             @OA\Property(property="name", type="string", example="Book Title"),
     *             @OA\Property(property="details", type="string", example="Book description goes here.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Book added successfully."),
     *             @OA\Property(property="data", ref="#/components/schemas/BookResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=412,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|unique:books,name',
            'details' => 'required'
        ];
        if ($this->apiValidator($request->all(), $rules)) {
            try {
                $book = Book::create([
                    'name' => $request->name,
                    'slug' => Str::slug($request->name),
                    'details' => $request->details
                ]);
                Cache::forget("books_*");
                $this->status = $this->statusCode["success"];
                $this->response["data"] = new BookResource($book);
                $this->response["message"] = "Book added sucessfully";
            } catch (Exception $e) {
                $this->status = $this->statusCode["internal_server_error"];
                $this->response["message"] = "Something went wrong.";
                // $this->response["error"] = $e->getMessage();
            }
        }
        return response()->json($this->response, $this->status);
    }

    /**
     * @OA\Get(
     *     path="/api/books/{id}",
     *     summary="Get book details",
     *     description="Retrieve details of a specific book by its ID.",
     *     tags={"Books"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the book to retrieve",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Book details fetched successfully."),
     *             @OA\Property(property="data", ref="#/components/schemas/BookResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Book not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No book found with this ID, please try again with a valid ID.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function show(string $id)
    {
        try {
            $book = book::where('id', $id)->first();
            if ($book) {
                $this->status = $this->statusCode["success"];
                $this->response["data"] = new BookResource($book);
                $this->response["message"] = "book details fetch sucessfully";
            } else {
                $this->status = $this->statusCode["not_found"];
                $this->response["data"] = [];
                $this->response["message"] = "No any book found with this id, please try again with vaid id.";
            }
        } catch (Exception $e) {
            $this->status = $this->statusCode["internal_server_error"];
            $this->response["message"] = "Something went wrong";
            // $this->response["error"] = $e->getMessage();
        }
        return response()->json($this->response, $this->status);
    }


    /**
     * @OA\Put(
     *     path="/api/books/{id}",
     *     summary="Update an existing book",
     *     description="Update the details of an existing book, including its name and details.",
     *     tags={"Books"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the book to be updated",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Book data to be updated",
     *         @OA\JsonContent(
     *             required={"name", "details"},
     *             @OA\Property(property="name", type="string", example="Updated Book Title"),
     *             @OA\Property(property="details", type="string", example="Updated book description goes here.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book details updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Book details updated successfully."),
     *             @OA\Property(property="data", ref="#/components/schemas/BookResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Book not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $rules = [
            'name' => 'required|unique:books,name,' . $id,
            'details' => 'required'
        ];
        if ($this->apiValidator($request->all(), $rules)) {
            try {
                $book = Book::where('id', $id)->first();
                if ($book) {
                    $book->name = $request->name;
                    $book->slug = Str::slug($request->name);
                    $book->details = $request->details;
                    $book->save();
                    Cache::forget("books_*");
                    $this->status = $this->statusCode["success"];
                    $this->response["data"] = new BookResource($book);
                    $this->response["message"] = "Book details updated sucessfully";
                } else {
                    $this->status = $this->statusCode["not_found"];
                    $this->response["data"] = [];
                    $this->response["message"] = "No any book found with this id, please try again with vaid id.";
                }
            } catch (Exception $e) {
                $this->status = $this->statusCode["internal_server_error"];
                $this->response["message"] = "Something went wrong";
                // $this->response["error"] = $e->getMessage();
            }
        }
        return response()->json($this->response, $this->status);
    }

    /**
     * @OA\Delete(
     *     path="/api/books/{id}",
     *     summary="Delete a book",
     *     description="Delete a book from the system. If the book is borrowed, it cannot be deleted.",
     *     tags={"Books"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the book to be deleted",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Book deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Book not found"
     *     ),
     *     @OA\Response(
     *         response=412,
     *         description="Precondition failed: Book is currently borrowed"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function destroy(string $id)
    {
        try {
            $check = validateBook($id);
            if ($check) {
                if (!empty($check->borrow_user_id)) {
                    $this->status = $this->statusCode["precondition_failed"];
                    $this->response["data"] = [];
                    $this->response["message"] = "You can not delete this book, because this book already borrowed by someone.";
                } else {
                    $check->delete();
                    $this->status = $this->statusCode["success"];
                    $this->response["message"] = "Book deleted sucessfully";
                    Cache::forget("books_*");
                }
            } else {
                $this->status = $this->statusCode["not_found"];
                $this->response["data"] = [];
                $this->response["message"] = "No any book found with this id, please try again with vaid id.";
            }
        } catch (Exception $e) {
            $this->status = $this->statusCode["internal_server_error"];
            $this->response["message"] = "Something went wrong";
            // $this->response["error"] = $e->getMessage();
        }
        return response()->json($this->response, $this->status);
    }

    /**
     * @OA\Post(
     *     path="/api/borrow-book",
     *     summary="Borrow a book",
     *     description="Allow users to borrow a book if it's available. The book's status is updated, and a record is created in the borrow history.",
     *     tags={"Books"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\RequestBody(
     *         required=true,
     *          @OA\JsonContent(
     *             type="object",
     *             required={"book_id"},
     *             @OA\Property(property="book_id", type="string", example="Borrow book id")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book borrowed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Book borrowed successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request, user has already borrowed this book or the book is already borrowed by another user"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Book not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function borrowBook(Request $request)
    {
        $rules = [
            'book_id' => 'required'
        ];
        if ($this->apiValidator($request->all(), $rules)) {
            try {
                $check_book_available = validateBook($request->book_id);
                $user = auth("api")->user();
                if (empty($check_book_available)) {
                    $this->status = $this->statusCode["not_found"];
                    $this->response["data"] = [];
                    $this->response["message"] = "No any book found with this id, please try again with vaid id.";
                } else {
                    if (!empty($check_book_available->borrow_user_id)) {
                        if ($check_book_available->borrow_user_id == $user->id) {
                            $this->status = $this->statusCode["bad_request"];
                            $this->response["data"] = [];
                            $this->response["message"] = "You already borrow this book, Please try with another one.";
                        } else {
                            $this->status = $this->statusCode["bad_request"];
                            $this->response["data"] = [];
                            $this->response["message"] = "You can not borrow this book, this book is already borrow by another user.";
                        }
                    } else {
                        // Update book status
                        $check_book_available->borrow_user_id = $user->id;
                        $check_book_available->status = '0';
                        $check_book_available->save();

                        // Add records in borrow_history table
                        BorrowHistory::create([
                            'book_id' => $request->book_id,
                            'borrow_user_id' => $user->id,
                            'borrow_date' => now(),
                            'status' => 'B'
                        ]);
                        event(new BookBorrowed($user, $check_book_available));

                        $this->status = $this->statusCode["success"];
                        $this->response["data"] = [];
                        $this->response["message"] = "Book borrow sucessfully";
                    }
                }
            } catch (Exception $e) {
                $this->status = $this->statusCode["internal_server_error"];
                $this->response["message"] = "Something went wrong.";
                $this->response["error"] = $e->getMessage();
            }
        }
        return response()->json($this->response, $this->status);
    }

    /**
     * @OA\Post(
     *     path="/api/return-book",
     *     summary="Return a borrowed book",
     *     description="Allow users to return a book they have borrowed. The book's status is updated and a return entry is created in the borrow history.",
     *     tags={"Books"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"borrow_id", "book_id"},
     *             @OA\Property(property="borrow_id", type="string", example="1", description="ID of the borrow history record."),
     *             @OA\Property(property="book_id", type="string", example="1", description="ID of the book to return.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Book returned successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Book returned successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request, invalid book ID, borrow ID, or book return attempt"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Book not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function returnBook(Request $request)
    {
        $rules = [
            'borrow_id' => 'required',
            'book_id' => 'required'
        ];
        if ($this->apiValidator($request->all(), $rules)) {
            try {
                $check_book_available = validateBook($request->book_id);
                $user = auth("api")->user();
                if (empty($check_book_available)) {
                    $this->status = $this->statusCode["not_found"];
                    $this->response["data"] = [];
                    $this->response["message"] = "No any book found with this id, please try again with vaid id.";
                } else {
                    if (!empty($check_book_available->borrow_user_id)) {

                        $validate_borrow_book_details = BorrowHistory::where("id", $request->borrow_id)->where('book_id', $request->book_id)->first();
                        if (empty($validate_borrow_book_details)) {
                            $this->status = $this->statusCode["bad_request"];
                            $this->response["data"] = [];
                            $this->response["message"] = "Invalid Book Id Or Borrow id, please try again with valid ids.";
                        } else {
                            if ($validate_borrow_book_details->status == 'R' && !empty($validate_borrow_book_details->return_date)) {
                                $this->status = $this->statusCode["bad_request"];
                                $this->response["data"] = [];
                                $this->response["message"] = "You can not return multiple same book with multiple times. Please try again with other details.";
                            } else {
                                if ($check_book_available->borrow_user_id == $user->id) {
                                    $check_book_available->borrow_user_id = null;
                                    $check_book_available->status = '1';
                                    $check_book_available->save();

                                    BorrowHistory::where('borrow_user_id', $user->id)
                                        ->where('id', $request->borrow_id)
                                        ->where('book_id', $request->book_id)
                                        ->update([
                                            'return_date' => now(),
                                            'status' => 'R'
                                        ]);

                                    $this->status = $this->statusCode["success"];
                                    $this->response["data"] = [];
                                    $this->response["message"] = "Book return sucessfully.";
                                } else {
                                    $this->status = $this->statusCode["bad_request"];
                                    $this->response["data"] = [];
                                    $this->response["message"] = "You haven't borrow this book, they you can not return this book.";
                                }
                            }
                        }
                    } else {
                        $this->status = $this->statusCode["bad_request"];
                        $this->response["data"] = [];
                        $this->response["message"] = "No one has borrowed this book yet.";
                    }
                }
            } catch (Exception $e) {
                $this->status = $this->statusCode["internal_server_error"];
                $this->response["message"] = "Something went wrong.";
                $this->response["error"] = $e->getMessage();
            }
        }
        return response()->json($this->response, $this->status);
    }

    /**
     * @OA\Get(
     *     path="/api/borrowed-books",
     *     summary="Get a list of borrowed books",
     *     description="Retrieve a list of books that the authenticated user has borrowed. Supports pagination and search functionality.",
     *     tags={"Books"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of results per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="search_term",
     *         in="query",
     *         description="Search term to filter borrowed books by name",
     *         required=false,
     *         @OA\Schema(type="string", example="Harry Potter")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully fetched borrowed books",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Books retrieved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="borrowing_history",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/BookHistoryResource")
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=25)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function getBorrowedBookList(Request $request)
    {
        try {
            $page = $request->page ? $request->page : 1;
            $limit = $request->limit ? $request->limit : 10;

            $user = auth("api")->user();
            $borrowed_list = $user->borrowings()->with('book');

            if (!empty($request->search_term)) {
                $searchTerm = $request->search_term;

                $borrowed_list = $borrowed_list->whereHas('book', function ($query) use ($searchTerm) {
                    $query->where('name', 'LIKE', "%$searchTerm%");
                });
            }

            $total = $borrowed_list->count();
            if (isset($page) && isset($limit) && $page > 0 && $limit >= 0) {
                $offset = ($page - 1) * $limit;
                $borrowed_list->offset($offset)->limit($limit);
            }

            $borrowed_list = $borrowed_list->get();
            $this->status = $this->statusCode["success"];
            $this->response["message"] = "Books get sucessfully.";
            $this->response["data"]["borrowing_history"] = BookHistoryResource::collection($borrowed_list);
            $this->response["data"]["total"] = $total;
            return response()->json($this->response, $this->status);
        } catch (Exception $exception) {
            $this->status = $this->statusCode["internal_server_error"];
            $this->response["message"] = "Something went wrong.";
            $this->response["error"] = $exception->getMessage();
            return response()->json($this->response, $this->status);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/books/{id}/borrowed-users",
     *     summary="Get a list of users who borrowed a specific book",
     *     description="Retrieve a list of users who have borrowed a specific book. Supports pagination and search functionality.",
     *     tags={"Books"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the book",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of results per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="search_term",
     *         in="query",
     *         description="Search term to filter users who borrowed the book (by name or email)",
     *         required=false,
     *         @OA\Schema(type="string", example="John Doe")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully fetched borrowed users for the book",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Details retrieved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="borrowing_history",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/BookWiseHistoryResource")
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=25)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Book not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function getBookwiseBorrowList(Request $request, $id)
    {
        try {
            $check_book_available = validateBook($id);
            if (empty($check_book_available)) {
                $this->status = $this->statusCode["not_found"];
                $this->response["data"] = [];
                $this->response["message"] = "No any book found with this id, please try again with vaid id.";
            } else {
                $page = $request->page ? $request->page : 1;
                $limit = $request->limit ? $request->limit : 10;
                $borrowed_list = $check_book_available->borrowings()->with('user');
                if (isset($request->search_term) && $request->search_term != "") {
                    $searchTerm = $request->search_term;
                    $borrowed_list = $borrowed_list->whereHas('user', function ($query) use ($searchTerm) {
                        $query->where('name', 'LIKE', "%$searchTerm%")->orWhere('email', 'LIKE', "%$searchTerm%");
                    });
                }
                $total = $borrowed_list->count();
                if (isset($page) && isset($limit) && $page > 0 && $limit >= 0) {
                    $offset = ($page - 1) * $limit;
                    $borrowed_list->offset($offset)->limit($limit);
                }
                $borrowed_list = $borrowed_list->get();
                $this->status = $this->statusCode["success"];
                $this->response["message"] = "Details get sucessfully.";
                $this->response["data"]["borrowing_history"] = BookWiseHistoryResource::collection($borrowed_list);
                $this->response["data"]["total"] = $total;
                return response()->json($this->response, $this->status);
            }
        } catch (Exception $e) {
            $this->status = $this->statusCode["internal_server_error"];
            $this->response["message"] = "Something went wrong.";
            $this->response["error"] = $e->getMessage();
        }
        return response()->json($this->response, $this->status);
    }
}
