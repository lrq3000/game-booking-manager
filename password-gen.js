/*
 * Copyright (C) 2011-2013 Larroque Stephen
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the Affero GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

/**
 * \description     g_password generator (Human readable password generator for game server's g_password)
 */

var strChunk = new Array("cane","gain","leaf","lean",
"lech","book","prim","rose","roto","rote","sift","side",
"spur","time","tint","tire","mess","will","wave","weak",
"year","yard","wick","wild","view","lock","tent","sold",
"slab","sent","tiptoe","tiptoed","timetable","timetabled","timetables","timid","last","flame","water","earth","falcon","lightning","thunder","thunderous","furious","one","two","three","four","five","six","seven","eight","nine","ten","effigy","crowned","crab","traveller","of","baldy","biomorphic","myths","past","soul","snipe","song","music","god","deusexmachina","leilol","sago", "fluffy")

function randPassword(input)
{
	nbwords = 3;

	input.value = "";
	for (i=0;i<nbwords;i++) {
		input.value += strChunk[Math.round(Math.random() * strChunk.length)];
		if (i != nbwords - 1) {
			input.value += "-";
		}
	}
}