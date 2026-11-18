<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\StoreRoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Models\Room;

class RoomController extends Controller
{
    /**
     * Display a listing of the rooms.
     */
    public function index()
    {
        $rooms = Room::latest()->get();

        return view('admin.rooms.index', compact('rooms'));
    }

    /**
     * Show the form for creating a new room.
     */
    public function create()
    {
        return view('admin.rooms.create');
    }

    /**
     * Store a newly created room.
     */
    public function store(StoreRoomRequest $request)
    {
        Room::create($request->validated());

        session()->flash('success', 'Ruangan berhasil ditambahkan.');

        return redirect()->route('admin.master.rooms.index');
    }

    /**
     * Show the form for editing the specified room.
     */
    public function edit(Room $room)
    {
        return view('admin.rooms.edit', compact('room'));
    }

    /**
     * Update the specified room.
     */
    public function update(UpdateRoomRequest $request, Room $room)
    {
        $room->update($request->validated());

        session()->flash('success', 'Ruangan berhasil diperbarui.');

        return redirect()->route('admin.master.rooms.index');
    }

    /**
     * Remove the specified room.
     */
    public function destroy(Room $room)
    {
        // Mirror of the category guard: complaints.room_id is restrictOnDelete,
        // so an in-use room would otherwise die as a raw 500 on the FK
        // constraint instead of a readable message.
        if ($room->complaints()->exists()) {
            session()->flash('error', 'Ruangan tidak dapat dihapus karena masih dipakai pengaduan.');

            return redirect()->route('admin.master.rooms.index');
        }

        $room->delete();

        session()->flash('success', 'Ruangan berhasil dihapus.');

        return redirect()->route('admin.master.rooms.index');
    }
}
