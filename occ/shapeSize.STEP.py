#!/usr/bin/env python3

#conda install -c dlr-sc pythonocc-core
#conda install -c dlr-sc/label/dev pythonocc-core

from OCC.Extend.ShapeFactory import (get_aligned_boundingbox, get_oriented_boundingbox)
from OCC.Extend.DataExchange import (read_step_file)

import sys
import os

path = sys.argv[1]

def print_bounding_boxes(shape):
    # Prints the axis-aligned bounding box data as a tuple of:
    # Center of the AABB
    # Tuple of [x, y, z]
    # Bounding box as a solid
    #print('Prints the axis-aligned bounding box data as a tuple')
    print(get_aligned_boundingbox(shape))

    # Prints the oriented bounding box data as a tuple of:
    # CBaryCenter of the OBB
    # Tuple of half sizes [x, y, z]
    # Bounding box as a solid
    #print('Prints the oriented bounding box data as a tuple')
    #print(get_oriented_boundingbox(shape))

    # The AABB center can bo obtained as a Tuple of [x, y, z] using:
    # get_aligned_boundingbox(shape)[0].Coord()

    # The OBB rotation can be obtained as a Quaterion of [x, y, z, w] using:
    #quaternion = get_oriented_boundingbox(shape)[2].Location().Transformation().GetRotation()
    #print([quaternion.X(), quaternion.Y(), quaternion.Z(), quaternion.W()])

shp = read_step_file(path)
print_bounding_boxes(shp)
